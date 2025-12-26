<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<nav>
    <div style="font-weight: bold; letter-spacing: 2px;">BW BANK SYSTEM</div>
    <div>
        <span style="color: #666; margin-right: 15px;">USER: <?php echo strtoupper($_SESSION['username']); ?></span>
        <a href="/logout">ВИХІД</a>
    </div>
</nav>

<div style="display: flex; gap: 30px; flex-wrap: wrap;">

    <div style="flex: 0 0 350px;">
        <div class="bank-card">
            <div class="card-balance">
                <div><?php echo number_format($account['balance'], 2); ?> ₴</div>
                <div style="font-size: 0.6em; color: #aaa; margin-top: 5px;">
                    $ <?php echo number_format($account['balance_usd'] ?? 0, 2); ?> |
                    € <?php echo number_format($account['balance_eur'] ?? 0, 2); ?>
                </div>
            </div>
            <div class="card-chip"></div>
            <div class="card-number">
                <?php echo implode(' ', str_split($account['card_number'] ?? '0000', 4)); ?>
            </div>
            <div class="card-holder"><?php echo strtoupper($_SESSION['username']); ?></div>
        </div>

        <div class="module">
            <h3 style="margin-bottom: 10px;">ДИНАМІКА БАЛАНСУ</h3>
            <div class="chart-controls" style="display: flex; gap: 4px; flex-wrap: wrap; margin-bottom: 10px;">
                <button onclick="updateMainChart('5m')" class="btn-small btn-time">5хв</button>
                <button onclick="updateMainChart('1h')" class="btn-small btn-time">1г</button>
                <button onclick="updateMainChart('24h')" class="btn-small btn-time">24г</button>
                <button onclick="updateMainChart('all')" class="btn-small btn-time">ВСЕ</button>
            </div>
            <div style="position: relative; height: 160px; width: 100%;">
                <canvas id="balanceChart"></canvas>
            </div>
        </div>

        <div class="module">
            <h3>ОБМІН ВАЛЮТ</h3>
            <div style="display: flex; gap: 10px;">
                <form action="/exchange" method="POST" style="flex:1">
                    <small>USD (41.50)</small>
                    <input type="hidden" name="currency" value="USD">
                    <input type="number" name="amount" placeholder="$" required style="padding: 5px;">
                    <div style="display:flex; gap:2px; margin-top:5px">
                        <button name="action" value="buy" class="btn-small">BUY</button>
                        <button name="action" value="sell" class="btn-small btn-outline">SELL</button>
                    </div>
                </form>
                <form action="/exchange" method="POST" style="flex:1">
                    <small>EUR (45.20)</small>
                    <input type="hidden" name="currency" value="EUR">
                    <input type="number" name="amount" placeholder="€" required style="padding: 5px;">
                    <div style="display:flex; gap:2px; margin-top:5px">
                        <button name="action" value="buy" class="btn-small">BUY</button>
                        <button name="action" value="sell" class="btn-small btn-outline">SELL</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="module">
            <h3>ШВИДКИЙ ПЕРЕКАЗ</h3>
            <form action="/transfer" method="POST">
                <input type="text" name="card_number" placeholder="Номер картки" required maxlength="25">
                <input type="number" name="amount" placeholder="Сума (UAH)" required>
                <button type="submit">НАДІСЛАТИ</button>
            </form>
        </div>
    </div>

    <div style="flex: 1;">

        <div class="grid-2">
            <div class="module">
                <h3>КРЕДИТИ</h3>
                <form action="/loan" method="POST" style="margin-bottom: 10px;">
                    <input type="number" name="amount" placeholder="Сума" required>
                    <button type="submit" class="btn-outline">ОТРИМАТИ (+15%)</button>
                </form>
                <?php if(!empty($loans)): ?>
                    <div class="scroll-box" style="height: 100px;">
                        <?php foreach($loans as $loan): ?>
                            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #222; padding: 5px 0;">
                                <span style="color: #f55;"><?php echo number_format($loan['amount']*1.15, 2); ?></span>
                                <form action="/pay-loan" method="POST" style="margin:0"><input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>"><button class="btn-small">PAY</button></form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="module">
                <h3>ДЕПОЗИТИ</h3>
                <form action="/deposit" method="POST" style="margin-bottom: 10px;">
                    <input type="number" name="amount" placeholder="Сума" required>
                    <button type="submit" class="btn-outline">ВКЛАСТИ (10%)</button>
                </form>
                <?php $totalDep = 0; foreach($deposits as $d) $totalDep += $d['amount']; ?>
                <div style="text-align: center; border: 1px solid #333; padding: 10px;">
                    ВСЬОГО: <strong style="color: #4f4;"><?php echo number_format($totalDep, 2); ?> ₴</strong>
                </div>
            </div>
        </div>

        <div class="module" style="margin-top: 20px;">
            <h3>РИНОК АКЦІЙ</h3>
            <table>
                <tr>
                    <th>КОМПАНІЯ</th>
                    <th>ЦІНА</th>
                    <th>У ВАС</th>
                    <th>АНАЛІЗ</th>
                    <th style="width: 30%">ТОРГІВЛЯ</th>
                </tr>
                <?php foreach($stocks as $index => $stock): ?>
                    <tr>
                        <td>
                            <strong style="color: #fff;"><?php echo $stock['info']['symbol']; ?></strong><br>
                            <small style="color:#666"><?php echo $stock['info']['name']; ?></small>
                        </td>
                        <td style="color: #fff; font-weight: bold;">
                            <?php echo number_format($stock['price'], 2); ?>
                        </td>
                        <td style="color: #4f4; font-weight: bold;">
                            <?php echo $stock['user_quantity']; ?>
                        </td>
                        <td>
                            <button id="btn_graph_<?php echo $index; ?>" onclick="toggleStockGraph(<?php echo $index; ?>)" class="btn-small btn-outline" style="border-color: #4df; color: #4df;">ГРАФІК</button>
                        </td>
                        <td>
                            <form action="/trade" method="POST" style="margin: 0; display: flex; gap: 5px; align-items: center;">
                                <input type="hidden" name="stock_id" value="<?php echo $stock['info']['id']; ?>">
                                <input type="number" name="amount" value="1" min="1" style="width: 40px; padding: 5px; margin: 0; text-align: center;">
                                <button type="submit" name="action" value="buy" class="btn-small">BUY</button>
                                <?php if ($stock['user_quantity'] > 0): ?>
                                    <button type="submit" name="action" value="sell" class="btn-small btn-outline" style="border-color: #f44; color: #f44;">SELL</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="module" style="margin-top: 20px; border: 1px solid #4df; display:none; background: #080808;" id="stockInspector">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h3 style="margin: 0; color: #4df;" id="stockTitle">ОБЕРІТЬ АКЦІЮ</h3>
                <h2 style="margin: 0; color: #fff; text-shadow: 0 0 10px rgba(0,255,255,0.5);" id="stockPrice">---</h2>
            </div>

            <div class="chart-controls" style="margin-bottom: 10px;">
                <button onclick="updateStockChart('5m')" class="btn-small btn-time">5хв</button>
                <button onclick="updateStockChart('30m')" class="btn-small btn-time">30хв</button>
                <button onclick="updateStockChart('1h')" class="btn-small btn-time">1г</button>
                <button onclick="updateStockChart('all')" class="btn-small btn-time">ВСЕ</button>
            </div>

            <div style="position: relative; height: 250px; width: 100%;">
                <canvas id="bigStockChart"></canvas>
            </div>
        </div>

        <div class="module" style="margin-top: 20px;">
            <h3>ІСТОРІЯ</h3>
            <div class="scroll-box" style="height: 150px;">
                <table>
                    <?php foreach($transactions as $t): ?>
                        <tr>
                            <td><?php echo strtoupper($t['type']); ?></td>
                            <td><?php echo $t['description']; ?></td>
                            <td style="text-align: right; color: #aaa;">
                                <?php echo number_format($t['amount'], 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
    const stocksData = <?php echo json_encode($stocks); ?>;
    const balanceHistory = <?php echo json_encode($balance_history); ?>;

    // --- 1. ГРАФІК БАЛАНСУ ---
    const ctxBalance = document.getElementById('balanceChart').getContext('2d');
    let balanceChart = null;

    function renderBalanceChart(dataPoints) {
        if (balanceChart) balanceChart.destroy();
        const labels = dataPoints.map(i => {
            const d = new Date(i.recorded_at);
            return (d.getHours()<10?'0':'')+d.getHours()+':'+(d.getMinutes()<10?'0':'')+d.getMinutes();
        });
        const vals = dataPoints.map(i => i.balance);
        let grad = ctxBalance.createLinearGradient(0, 0, 0, 200);
        grad.addColorStop(0, 'rgba(255, 255, 255, 0.1)');
        grad.addColorStop(1, 'rgba(255, 255, 255, 0)');

        balanceChart = new Chart(ctxBalance, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    data: vals,
                    borderColor: '#fff',
                    backgroundColor: grad,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#000',
                    pointBorderColor: '#fff',
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { x: { display: false }, y: { display: false } },
                plugins: { legend: { display: false }, tooltip: { intersect: false, mode: 'index' } }
            }
        });
    }

    function updateMainChart(period) {
        const now = new Date();
        let filtered = balanceHistory;

        const buttons = document.querySelectorAll('.module:nth-child(2) .chart-controls button');
        buttons.forEach(b => b.style.background = 'transparent');
        event.target.style.background = '#333';

        if(period !== 'all') {
            const cut = new Date();
            if(period==='5m') cut.setMinutes(now.getMinutes()-5);
            if(period==='30m') cut.setMinutes(now.getMinutes()-30);
            if(period==='1h') cut.setHours(now.getHours()-1);
            if(period==='6h') cut.setHours(now.getHours()-6);
            if(period==='24h') cut.setHours(now.getHours()-24);
            filtered = balanceHistory.filter(i => new Date(i.recorded_at) >= cut);
        }
        if(filtered.length === 0 && balanceHistory.length > 0) filtered = [balanceHistory[balanceHistory.length-1]];
        renderBalanceChart(filtered);
    }
    renderBalanceChart(balanceHistory);


    // --- 2. ІНСПЕКТОР АКЦІЙ (ПЕРЕМИКАЧ) ---
    const ctxStock = document.getElementById('bigStockChart').getContext('2d');
    let stockChart = null;
    let currentStockHistory = [];
    let currentlyOpenIndex = null; // Запам'ятовуємо, що зараз відкрито

    function toggleStockGraph(index) {
        const inspector = document.getElementById('stockInspector');

        // ЛОГІКА ПЕРЕМИКАННЯ:
        // Якщо натиснули на ту саму кнопку, що вже відкрита -> закриваємо
        if (currentlyOpenIndex === index && inspector.style.display === 'block') {
            inspector.style.display = 'none';
            currentlyOpenIndex = null;
            return; // Виходимо, далі нічого не робимо
        }

        // Інакше -> відкриваємо (або перемикаємо на іншу)
        currentlyOpenIndex = index;
        const stock = stocksData[index];
        inspector.style.display = 'block';

        // Прокрутка до графіка (для зручності)
        inspector.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        document.getElementById('stockTitle').innerText = stock.info.name + " (" + stock.info.symbol + ")";
        document.getElementById('stockPrice').innerText = parseFloat(stock.price).toFixed(2) + " ₴";

        currentStockHistory = stock.history;

        // Якщо історії немає, створюємо одну точку з поточною ціною, щоб графік не був пустим
        if (!currentStockHistory || currentStockHistory.length === 0) {
            currentStockHistory = [{
                recorded_at: new Date().toISOString(),
                price: stock.price
            }];
        }

        renderStockChart(currentStockHistory);
    }

    function renderStockChart(dataPoints) {
        if (stockChart) stockChart.destroy();

        const labels = dataPoints.map(i => {
            const d = new Date(i.recorded_at);
            return (d.getHours()<10?'0':'')+d.getHours()+':'+(d.getMinutes()<10?'0':'')+d.getMinutes();
        });
        const vals = dataPoints.map(i => i.price);

        const isUp = vals.length > 1 ? vals[vals.length-1] >= vals[0] : true;
        const color = isUp ? '#00ffcc' : '#ff0055';

        let grad = ctxStock.createLinearGradient(0, 0, 0, 300);
        grad.addColorStop(0, isUp ? 'rgba(0, 255, 204, 0.4)' : 'rgba(255, 0, 85, 0.4)');
        grad.addColorStop(1, 'rgba(0, 0, 0, 0)');

        stockChart = new Chart(ctxStock, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ціна',
                    data: vals,
                    borderColor: color,
                    backgroundColor: grad,
                    borderWidth: 3,
                    pointRadius: 0,
                    pointHoverRadius: 8,
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: color,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { display: false },
                    y: {
                        grid: { color: '#222' },
                        ticks: { color: '#666' }
                    }
                },
                plugins: { legend: { display: false } },
                elements: {
                    line: {
                        shadowBlur: 10,
                        shadowColor: color
                    }
                }
            }
        });
    }

    function updateStockChart(period) {
        if(currentStockHistory.length === 0) return;
        const now = new Date();
        let filtered = currentStockHistory;

        const buttons = document.querySelectorAll('#stockInspector .chart-controls button');
        buttons.forEach(b => b.style.background = 'transparent');
        event.target.style.background = '#333';

        if(period !== 'all') {
            const cut = new Date();
            if(period==='5m') cut.setMinutes(now.getMinutes()-5);
            if(period==='30m') cut.setMinutes(now.getMinutes()-30);
            if(period==='1h') cut.setHours(now.getHours()-1);
            filtered = currentStockHistory.filter(i => new Date(i.recorded_at) >= cut);
        }
        if(filtered.length === 0) filtered = [currentStockHistory[currentStockHistory.length-1]];
        renderStockChart(filtered);
    }
</script>

<style>
    .btn-time { padding: 3px 6px; font-size: 0.7em; min-width: 30px; border: 1px solid #333; color: #aaa; cursor: pointer; }
    .btn-time:hover { color: #fff; border-color: #fff; }
</style>