<?php
namespace App\Service;

class BankService {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db->getConnection();
    }

    public function getUserData($userId) {
        $account = $this->getAccount($userId);

        $this->updateMarketPrices(); // Оновлюємо ринок

        $stmtTrans = $this->db->prepare("SELECT * FROM transactions WHERE account_id = ? ORDER BY created_at DESC LIMIT 20");
        $stmtTrans->execute([$account['id']]);

        $stmtLoans = $this->db->prepare("SELECT * FROM loans WHERE user_id = ? AND is_paid = 0");
        $stmtLoans->execute([$userId]);

        $stmtDeposits = $this->db->prepare("SELECT * FROM deposits WHERE user_id = ?");
        $stmtDeposits->execute([$userId]);

        // Історія балансу (багато точок для детального графіка)
        $stmtHistory = $this->db->prepare("SELECT balance, recorded_at FROM balance_history WHERE user_id = ? ORDER BY recorded_at ASC LIMIT 1000");
        $stmtHistory->execute([$userId]);
        $history = $stmtHistory->fetchAll();

        if (empty($history)) {
            $this->db->prepare("INSERT INTO balance_history (user_id, balance) VALUES (?, ?)")->execute([$userId, $account['balance']]);
            $history = [['balance' => $account['balance'], 'recorded_at' => date('Y-m-d H:i:s')]];
        }

        return [
            'account' => $account,
            'transactions' => $stmtTrans->fetchAll(),
            'loans' => $stmtLoans->fetchAll(),
            'deposits' => $stmtDeposits->fetchAll(),
            'stocks' => $this->getStocksData($userId),
            'balance_history' => $history
        ];
    }

    // --- ЛОГІКА РИНКУ ---
    private function updateMarketPrices() {
        // Оновлюємо ціну раз на хвилину, щоб графіки були живі
        $stmt = $this->db->query("SELECT recorded_at FROM stock_history ORDER BY recorded_at DESC LIMIT 1");
        $lastUpdate = $stmt->fetchColumn();

        if (!$lastUpdate || (time() - strtotime($lastUpdate) > 60)) {
            $stocks = $this->db->query("SELECT * FROM stocks")->fetchAll();
            foreach ($stocks as $stock) {
                $stmt = $this->db->prepare("SELECT price FROM stock_history WHERE stock_id = ? ORDER BY recorded_at DESC LIMIT 1");
                $stmt->execute([$stock['id']]);
                $lastPrice = $stmt->fetchColumn() ?: $stock['base_price'];

                // Випадкове коливання
                $change = (rand(-200, 200) / 10000);
                $newPrice = $lastPrice * (1 + $change);

                $this->db->prepare("INSERT INTO stock_history (stock_id, price) VALUES (?, ?)")
                    ->execute([$stock['id'], $newPrice]);
            }
        }
    }

    private function getStocksData($userId) {
        $stocks = $this->db->query("SELECT * FROM stocks")->fetchAll();
        $result = [];
        foreach ($stocks as $stock) {
            // Поточна ціна
            $stmt = $this->db->prepare("SELECT price FROM stock_history WHERE stock_id = ? ORDER BY recorded_at DESC LIMIT 1");
            $stmt->execute([$stock['id']]);
            $currentPrice = $stmt->fetchColumn() ?: $stock['base_price'];

            // ІСТОРІЯ ЦІН: Беремо 500 останніх записів, щоб працювали фільтри 5хв/30хв
            $stmtHist = $this->db->prepare("SELECT price, recorded_at FROM stock_history WHERE stock_id = ? ORDER BY recorded_at ASC LIMIT 500");
            $stmtHist->execute([$stock['id']]);
            $history = $stmtHist->fetchAll();

            // Кількість у юзера
            $stmtUser = $this->db->prepare("SELECT quantity FROM user_stocks WHERE user_id = ? AND stock_id = ?");
            $stmtUser->execute([$userId, $stock['id']]);
            $quantity = $stmtUser->fetchColumn() ?: 0;

            $result[] = [
                'info' => $stock,
                'price' => $currentPrice,
                'history' => $history,
                'user_quantity' => $quantity
            ];
        }
        return $result;
    }

    public function buyStock($userId, $stockId, $quantity) {
        if ($quantity <= 0) throw new \Exception("Error");
        $stock = $this->getStockInfo($stockId);
        $total = $stock['price'] * $quantity;
        $acc = $this->getAccount($userId);
        if ($acc['balance'] < $total) throw new \Exception("No money");

        $this->db->beginTransaction();
        try {
            $this->updateBalanceDirect($acc['id'], -$total);
            // ON DUPLICATE KEY UPDATE спрацює завдяки SQL фіксу з Кроку 1
            $this->db->prepare("INSERT INTO user_stocks (user_id, stock_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?")
                ->execute([$userId, $stockId, $quantity, $quantity]);

            $this->logTransaction($acc['id'], 'exchange_buy', $total, "BUY $quantity {$stock['symbol']}");
            $this->db->commit();
        } catch (\Exception $e) { $this->db->rollBack(); throw $e; }
    }

    public function sellStock($userId, $stockId, $quantity) {
        if ($quantity <= 0) throw new \Exception("Error");
        $stmt = $this->db->prepare("SELECT quantity FROM user_stocks WHERE user_id = ? AND stock_id = ?");
        $stmt->execute([$userId, $stockId]);
        $owned = $stmt->fetchColumn() ?: 0;

        if ($owned < $quantity) throw new \Exception("No stocks");

        $stock = $this->getStockInfo($stockId);
        $total = $stock['price'] * $quantity;
        $acc = $this->getAccount($userId);

        $this->db->beginTransaction();
        try {
            $this->updateBalanceDirect($acc['id'], $total);
            $this->db->prepare("UPDATE user_stocks SET quantity = quantity - ? WHERE user_id = ? AND stock_id = ?")
                ->execute([$quantity, $userId, $stockId]);
            $this->logTransaction($acc['id'], 'exchange_sell', $total, "SELL $quantity {$stock['symbol']}");
            $this->db->commit();
        } catch (\Exception $e) { $this->db->rollBack(); throw $e; }
    }

    // --- ОБМІН ВАЛЮТ ---
    public function exchangeCurrency($userId, $currency, $action, $amount) {
        if ($amount <= 0) throw new \Exception("Error");
        $rates = ['USD' => 41.50, 'EUR' => 45.20];
        $rate = $rates[$currency];
        $acc = $this->getAccount($userId);
        $totalUAH = $amount * $rate;
        $this->db->beginTransaction();
        try {
            if ($action == 'buy') {
                if ($acc['balance'] < $totalUAH) throw new \Exception("No money");
                $this->updateBalanceDirect($acc['id'], -$totalUAH);
                $col = 'balance_'.strtolower($currency);
                $this->db->prepare("UPDATE accounts SET $col = $col + ? WHERE id = ?")->execute([$amount, $acc['id']]);
                $this->logTransaction($acc['id'], 'exchange_buy', $totalUAH, "BUY $amount $currency");
            } else {
                $col = 'balance_'.strtolower($currency);
                if ($acc[$col] < $amount) throw new \Exception("No currency");
                $this->db->prepare("UPDATE accounts SET $col = $col - ? WHERE id = ?")->execute([$amount, $acc['id']]);
                $this->updateBalanceDirect($acc['id'], $totalUAH);
                $this->logTransaction($acc['id'], 'exchange_sell', $totalUAH, "SELL $amount $currency");
            }
            $this->db->commit();
        } catch (\Exception $e) { $this->db->rollBack(); throw $e; }
    }

    // --- ІНШІ МЕТОДИ (Стандартні) ---
    public function transfer($uid, $card, $amount) {
        if ($amount <= 0) throw new \Exception("Error");
        $s = $this->getAccount($uid);
        if ($s['balance'] < $amount) throw new \Exception("No money");
        $r = $this->db->prepare("SELECT * FROM accounts WHERE card_number = ?");
        $r->execute([$card]); $rec = $r->fetch();
        if (!$rec) throw new \Exception("No card");

        $this->db->beginTransaction();
        $this->updateBalanceDirect($s['id'], -$amount);
        $this->logTransaction($s['id'], 'transfer_out', $amount, "To $card");
        $this->updateBalanceDirect($rec['id'], $amount);
        $this->logTransaction($rec['id'], 'transfer_in', $amount, "From {$s['card_number']}");
        $this->db->commit();
    }
    public function takeLoan($uid, $amt) {
        $this->db->beginTransaction();
        $this->db->prepare("INSERT INTO loans (user_id, amount, interest_rate) VALUES (?, ?, 15)")->execute([$uid, $amt]);
        $acc = $this->getAccount($uid);
        $this->updateBalanceDirect($acc['id'], $amt);
        $this->logTransaction($acc['id'], 'loan', $amt, 'Credit');
        $this->db->commit();
    }
    public function payLoan($uid, $lid) {
        $l = $this->db->query("SELECT * FROM loans WHERE id=$lid")->fetch();
        $pay = $l['amount']*1.15;
        $acc = $this->getAccount($uid);
        if($acc['balance']<$pay) throw new \Exception("No money");
        $this->db->beginTransaction();
        $this->updateBalanceDirect($acc['id'], -$pay);
        $this->db->query("UPDATE loans SET is_paid=1 WHERE id=$lid");
        $this->logTransaction($acc['id'], 'loan_repay', $pay, "Pay Loan");
        $this->db->commit();
    }
    public function openDeposit($uid, $amt) {
        $acc = $this->getAccount($uid);
        if($acc['balance']<$amt) throw new \Exception("No money");
        $this->db->beginTransaction();
        $this->updateBalanceDirect($acc['id'], -$amt);
        $this->db->prepare("INSERT INTO deposits (user_id, amount, interest_rate) VALUES (?, ?, 10)")->execute([$uid, $amt]);
        $this->logTransaction($acc['id'], 'deposit', $amt, 'Deposit');
        $this->db->commit();
    }

    // Helpers
    private function getStockInfo($id) {
        $stmt = $this->db->prepare("SELECT price FROM stock_history WHERE stock_id = ? ORDER BY recorded_at DESC LIMIT 1");
        $stmt->execute([$id]);
        $price = $stmt->fetchColumn();
        $symbol = $this->db->query("SELECT symbol FROM stocks WHERE id = $id")->fetchColumn();
        return ['price' => $price, 'symbol' => $symbol];
    }
    private function getAccount($userId) { $stmt = $this->db->prepare("SELECT * FROM accounts WHERE user_id = ?"); $stmt->execute([$userId]); return $stmt->fetch(); }
    private function updateBalanceDirect($accountId, $amount) {
        $this->db->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?")->execute([$amount, $accountId]);
        $data = $this->db->query("SELECT user_id, balance FROM accounts WHERE id = $accountId")->fetch();
        $this->db->prepare("INSERT INTO balance_history (user_id, balance) VALUES (?, ?)")->execute([$data['user_id'], $data['balance']]);
    }
    private function logTransaction($aid, $type, $amount, $desc) {
        $this->db->prepare("INSERT INTO transactions (account_id, type, amount, description) VALUES (?, ?, ?, ?)")->execute([$aid, $type, abs($amount), $desc]);
    }
}