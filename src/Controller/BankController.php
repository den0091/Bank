<?php
namespace App\Controller;
use App\Service\BankService;
use App\Service\AuthService;
use App\Core\View;

class BankController {
    private $bankService;
    private $authService;

    public function __construct(BankService $bankService, AuthService $authService) {
        $this->bankService = $bankService;
        $this->authService = $authService;
    }

    public function index() {
        $userId = $this->authService->getCurrentUser();
        if (!$userId) { header('Location: /login'); exit; }
        $data = $this->bankService->getUserData($userId);
        View::render('dashboard', $data);
    }

    public function transfer() {
        $userId = $this->authService->getCurrentUser();

        // --- ВИПРАВЛЕННЯ: Видаляємо пробіли з номеру картки ---
        $cardNumber = str_replace(' ', '', $_POST['card_number']);

        try {
            $this->bankService->transfer($userId, $cardNumber, $_POST['amount']);
        } catch (\Exception $e) {}
        header('Location: /dashboard');
    }

    // Інші методи без змін
    public function exchange() {
        $userId = $this->authService->getCurrentUser();
        try { $this->bankService->exchangeCurrency($userId, $_POST['currency'], $_POST['action'], $_POST['amount']); } catch (\Exception $e) {}
        header('Location: /dashboard');
    }
    public function loan() {
        $userId = $this->authService->getCurrentUser();
        try { $this->bankService->takeLoan($userId, $_POST['amount']); } catch (\Exception $e) {}
        header('Location: /dashboard');
    }
    public function payLoan() {
        $userId = $this->authService->getCurrentUser();
        try { $this->bankService->payLoan($userId, $_POST['loan_id']); } catch (\Exception $e) {}
        header('Location: /dashboard');
    }
    public function deposit() {
        $userId = $this->authService->getCurrentUser();
        try { $this->bankService->openDeposit($userId, $_POST['amount']); } catch (\Exception $e) {}
        header('Location: /dashboard');
    }
    public function trade() {
        $userId = $this->authService->getCurrentUser();
        try {
            if ($_POST['action'] == 'buy') $this->bankService->buyStock($userId, $_POST['stock_id'], $_POST['amount']);
            else $this->bankService->sellStock($userId, $_POST['stock_id'], $_POST['amount']);
        } catch (\Exception $e) {}
        header('Location: /dashboard');
    }
}