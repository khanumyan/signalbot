<div class="content-section">
    <h2 class="section-title">📊 Логика работы</h2>
    <div class="section-content">
        <p>Универсальная трендовая стратегия, использующая пересечения EMA, импульс MACD и фильтр RSI для определения направления тренда.</p>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">🟢 Условия для BUY сигналов</h2>
    <div class="section-content">
        <ul>
            <li>Цена > EMA20 и EMA20 > EMA50 и MACD > 0 и Histogram > 0 и RSI < 70 (+40 баллов)
                <ul>
                    <li>Если RSI 40-60 и |Histogram| > 0.5: +30 баллов (сильный импульс)</li>
                    <li>Если RSI > 30 и |Histogram| > 0.2: +15 баллов (средний импульс)</li>
                </ul>
            </li>
            <li>Или просто: цена > EMA20 и EMA20 > EMA50 (+20 баллов)</li>
            <li>RSI ≤ 30 (+20 баллов)</li>
            <li>MACD Histogram > 0 и MACD Line > Signal (+10 баллов)</li>
        </ul>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">🔴 Условия для SELL сигналов</h2>
    <div class="section-content">
        <ul>
            <li>Цена < EMA20 и EMA20 < EMA50 и MACD < 0 и Histogram < 0 и RSI > 30 (+40 баллов)
                <ul>
                    <li>Если RSI 40-60 и |Histogram| > 0.5: +30 баллов</li>
                    <li>Если RSI < 70 и |Histogram| > 0.2: +15 баллов</li>
                </ul>
            </li>
            <li>Или просто: цена < EMA20 и EMA20 < EMA50 (+20 баллов)</li>
            <li>RSI ≥ 70 (+20 баллов)</li>
            <li>MACD Histogram < 0 и MACD Line < Signal (+10 баллов)</li>
        </ul>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">💪 Определение силы</h2>
    <div class="section-content">
        <table>
            <thead>
                <tr>
                    <th>Сила</th>
                    <th>Условие</th>
                </tr>
            </thead>
            <tbody>
                <tr><td><strong>STRONG</strong></td><td>разница вероятностей > 20%</td></tr>
                <tr><td><strong>MEDIUM</strong></td><td>разница вероятностей > 10%</td></tr>
                <tr><td><strong>WEAK</strong></td><td>остальное</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">📉 Стоп-лосс и тейк-профит</h2>
    <div class="section-content">
        <h3>Параметры:</h3>
        <ul>
            <li>SL Multiplier: 2.3 (по умолчанию)</li>
            <li>TP Multiplier: 2.0 (по умолчанию)</li>
        </ul>
        <h3>BUY:</h3>
        <div class="code-block">
            SL = Цена - (ATR × 2.3)<br>
            TP = Цена + (ATR × 2.0)
        </div>
        <h3>SELL:</h3>
        <div class="code-block">
            SL = Цена + (ATR × 2.3)<br>
            TP = Цена - (ATR × 2.0)
        </div>
    </div>
</div>



