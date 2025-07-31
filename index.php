<?php
$dataFile = 'stocks.json';
$stocks = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];

$ticker = strtoupper(trim($_GET['ticker'] ?? ''));

function getWinRate($trades) {
    if (!$trades) return 0;
    $wins = count(array_filter($trades, fn($t) => $t['outcome'] === 'win'));
    $total = count($trades);
    return $total ? round($wins / $total * 100, 1) : 0;
}

function winRateColor($winRate) {
    $rate = max(0, min(100, $winRate));
    $red = (int)(180 * (100 - $rate) / 100);
    $green = (int)(180 * $rate / 100);
    return "rgb($red,$green,0)";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_stock') {
            $newTicker = strtoupper(trim($_POST['new_ticker']));
            if ($newTicker && !isset($stocks[$newTicker])) {
                $stocks[$newTicker] = ['notes' => '', 'trades' => []];
            }
            file_put_contents($dataFile, json_encode($stocks));
            header("Location: ?ticker=$newTicker");
            exit;
        }
        if ($_POST['action'] === 'save_data' && $ticker && isset($stocks[$ticker])) {
            if (isset($_POST['notes'])) {
                $stocks[$ticker]['notes'] = $_POST['notes'];
            }
            if (!empty($_POST['trade_date']) && isset($_POST['trade_result']) && isset($_POST['trade_outcome'])) {
                $trade = [
                    'date' => $_POST['trade_date'],
                    'result' => floatval($_POST['trade_result']),
                    'outcome' => $_POST['trade_outcome']
                ];
                $stocks[$ticker]['trades'][] = $trade;
            }
            file_put_contents($dataFile, json_encode($stocks));
            header("Location: ?ticker=$ticker");
            exit;
        }
        if ($_POST['action'] === 'delete_stock' && $ticker && isset($stocks[$ticker])) {
            unset($stocks[$ticker]);
            file_put_contents($dataFile, json_encode($stocks));
            header("Location: ?");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Stock Manager<?= $ticker ? " - $ticker" : '' ?></title>
<style>
  body {
    background-color: #1a2e1a;
    color: #d9fcd9;
    font-family: Arial, sans-serif;
    margin: 0; padding: 20px;
  }
  a.home-button {
    display: inline-block;
    background-color: #2e5937;
    color: white;
    text-decoration: none;
    padding: 8px 14px;
    border-radius: 5px;
    margin-bottom: 20px;
  }
  a.home-button:hover {
    background-color: #4caf50;
  }
  h1, h2 {
    color: #b7ffb7;
  }
  form textarea, form input, form select {
    width: 100%;
    padding: 10px;
    margin: 8px 0 15px;
    border-radius: 5px;
    border: 1px solid #4caf50;
    background-color: #284e28;
    color: white;
    font-size: 1em;
  }
  button, input[type=submit] {
    background-color: #4caf50;
    border: none;
    padding: 10px 20px;
    color: white;
    border-radius: 5px;
    cursor: pointer;
    font-size: 1em;
    margin-top: 10px;
  }
  button:hover, input[type=submit]:hover {
    background-color: #45a049;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
  }
  th, td {
    border: 1px solid #5a945a;
    padding: 10px;
    text-align: left;
  }
  th {
    background-color: #2e5937;
  }
  ul.stock-list {
    list-style: none;
    padding-left: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
  }
  ul.stock-list li {
  }
  a.stock-button {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 120px;
    height: 120px;
    border-radius: 20px;
    text-decoration: none;
    font-weight: bold;
    font-size: 1.3em;
    color: white;
    box-shadow: 0 3px 6px rgba(0,0,0,0.4);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    position: relative;
  }
  a.stock-button:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 12px rgba(0,0,0,0.6);
  }
  span.winrate-badge {
    position: absolute;
    top: 10px;
    right: 12px;
    background: rgba(0,0,0,0.4);
    border-radius: 12px;
    padding: 4px 9px;
    font-size: 0.9em;
    font-weight: normal;
  }
  form.delete-form input[type=submit] {
    background-color: #b33;
    margin-top: 25px;
  }
  form.delete-form input[type=submit]:hover {
    background-color: #d55;
  }
</style>
<script>
  function confirmDelete() {
    return confirm('Are you sure you want to DELETE this stock and ALL its data? This action cannot be undone.');
  }
</script>
</head>
<body>

<?php if (!$ticker): ?>
  <!-- HOME VIEW -->
  <h1>Robhinwalk 1.0</h1>
  <form method="post" class="add-stock-form" action="">
    <input type="hidden" name="action" value="add_stock" />
    <input name="new_ticker" type="text" placeholder="Enter stock ticker" required style="width: 200px; margin-right: 8px; padding: 8px; border-radius: 4px; border: 1px solid #4caf50; background-color: #284e28; color: white;" />
    <button type="submit">Add Stock</button>
  </form>

  <?php if (!empty($stocks)): ?>
    <h2>Your Stocks</h2>
    <ul class="stock-list">
      <?php foreach ($stocks as $stockTicker => $stockData): 
        $winRate = getWinRate($stockData['trades'] ?? []);
        $color = winRateColor($winRate);
      ?>
        <li>
          <a href="?ticker=<?= urlencode($stockTicker) ?>" class="stock-button" style="background-color: <?= $color ?>;">
            <?= htmlspecialchars($stockTicker) ?>
            <span class="winrate-badge"><?= $winRate ?>%</span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p>No stocks added yet.</p>
  <?php endif; ?>

<?php else: ?>
  <!-- DETAIL VIEW -->
  <a href="?" class="home-button">Home</a>
  <h1><?= htmlspecialchars($ticker) ?></h1>

  <?php
  $currentStock = $stocks[$ticker];
  $trades = $currentStock['trades'] ?? [];
  $wins = count(array_filter($trades, fn($t) => $t['outcome'] === 'win'));
  $totalTrades = count($trades);
  $winRate = $totalTrades ? round($wins / $totalTrades * 100, 1) : 0;
  ?>

  <form method="post" action="">
    <input type="hidden" name="action" value="save_data" />
    <label for="notes"><strong>📒 Strategy Notes</strong></label>
    <textarea id="notes" name="notes" rows="5"><?= htmlspecialchars($currentStock['notes']) ?></textarea>

    <h2>📊 Add Trade</h2>
    <label for="trade_date">Date</label>
    <input type="date" id="trade_date" name="trade_date" />

    <label for="trade_result">% Gain/Loss</label>
    <input type="number" id="trade_result" name="trade_result" step="0.01" placeholder="e.g. 2.5" />

    <label for="trade_outcome">Outcome</label>
    <select id="trade_outcome" name="trade_outcome">
      <option value="win">Win</option>
      <option value="loss">Loss</option>
    </select>

    <button type="submit">Save Note / Trade</button>
  </form>

  <form method="post" action="" class="delete-form" onsubmit="return confirmDelete()">
    <input type="hidden" name="action" value="delete_stock" />
    <button type="submit">Delete Stock</button>
  </form>

  <h2>📈 Trade Summary</h2>
  <p>Total Trades: <?= $totalTrades ?>, Wins: <?= $wins ?>, Win Rate: <?= $winRate ?>%</p>

  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>% Gain/Loss</th>
        <th>Outcome</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (array_reverse($trades) as $trade): ?>
        <tr>
          <td><?= htmlspecialchars($trade['date']) ?></td>
          <td><?= htmlspecialchars($trade['result']) ?>%</td>
          <td><?= ucfirst(htmlspecialchars($trade['outcome'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

</body>
</html>
