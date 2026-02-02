<div class="content-section">
    <h2 class="section-title">📊 Логика работы</h2>
    <div class="section-content">
        <p>Контртрендовая стратегия для боковых рынков, использующая отскоки от границ Bollinger Bands с подтверждением RSI.</p>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">🟢 Условия для BUY сигналов</h2>
    <div class="section-content">
        <ul>
            <li>Цена ≤ нижняя полоса BB × 1.005 и RSI < 30 (+50 баллов)
                <ul>
                    <li>Если RSI ≤ 20: +30 баллов (очень перепродан)</li>
                    <li>Если RSI ≤ 25: +15 баллов (перепродан)</li>
                </ul>
            </li>
            <li>Или цена ≤ нижняя полоса BB × 1.02: +20 баллов</li>
            <li>RSI ≤ 20: +20 баллов</li>
            <li>RSI < 30: +10 баллов</li>
            <li>Позиция в BB ≤ 10%: +15 баллов</li>
        </ul>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">🔴 Условия для SELL сигналов</h2>
    <div class="section-content">
        <ul>
            <li>Цена ≥ верхняя полоса BB × 0.995 и RSI > 70 (+50 баллов)
                <ul>
                    <li>Если RSI ≥ 80: +30 баллов (очень перекуплен)</li>
                    <li>Если RSI ≥ 75: +15 баллов (перекуплен)</li>
                </ul>
            </li>
            <li>Или цена ≥ верхняя полоса BB × 0.98: +20 баллов</li>
            <li>RSI ≥ 80: +20 баллов</li>
            <li>RSI > 70: +10 баллов</li>
            <li>Позиция в BB ≥ 90%: +15 баллов</li>
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
            <li>SL Multiplier: 2.0 (по умолчанию)</li>
            <li>TP Multiplier: 2.0 (по умолчанию)</li>
        </ul>
        <h3>BUY:</h3>
        <div class="code-block">
            SL = min(Цена - (ATR × 2.0), Нижняя полоса BB × 0.98)<br>
            Risk = Цена - SL<br>
            TP = min(Средняя полоса BB, Цена + (Risk × 2.0))
        </div>
        <h3>SELL:</h3>
        <div class="code-block">
            SL = max(Цена + (ATR × 2.0), Верхняя полоса BB × 1.02)<br>
            Risk = SL - Цена<br>
            TP = max(Средняя полоса BB, Цена - (Risk × 2.0))
        </div>
    </div>
</div>


