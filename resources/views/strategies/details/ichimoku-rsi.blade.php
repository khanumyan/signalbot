<div class="content-section">
    <h2 class="section-title">🔥 Новая рабочая логика</h2>
    <div class="section-content">
        <p>Долгосрочная трендовая стратегия с облачной поддержкой. Использует четкую логику входа без конфликтов, без балльной системы.</p>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">🟢 Четкая логика входа для BUY</h2>
    <div class="section-content">
        <ol>
            <li>Цена выше облака (Senkou A и B)</li>
            <li>Tenkan > Kijun</li>
            <li>Chikou выше цены 26 периодов назад</li>
            <li>RSI ∈ [45–65]</li>
            <li>Расстояние от облака ≤ 1 × ATR</li>
        </ol>
        <div class="warning-box">
            <strong>❌ НЕ входить:</strong>
            <ul style="margin-top: 8px;">
                <li>RSI > 70</li>
                <li>Цена слишком далеко от облака (> 1.5 × ATR)</li>
            </ul>
        </div>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">🔴 Четкая логика входа для SELL</h2>
    <div class="section-content">
        <ol>
            <li>Цена ниже облака</li>
            <li>Tenkan < Kijun</li>
            <li>Chikou ниже цены 26 периодов назад</li>
            <li>RSI ∈ [35–55]</li>
            <li>Расстояние от облака ≤ 1 × ATR</li>
        </ol>
        <div class="warning-box">
            <strong>❌ НЕ входить:</strong>
            <ul style="margin-top: 8px;">
                <li>RSI < 30</li>
                <li>Цена слишком далеко от облака (> 1.5 × ATR)</li>
            </ul>
        </div>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">🔧 Фильтры (обязательные)</h2>
    <div class="section-content">
        <h3>Фильтр плоского облака:</h3>
        <div class="code-block">
            |Senkou A − Senkou B| ≥ 0.5 × ATR
        </div>
        <div class="warning-box">
            <strong>❌ Если облако тонкое — рынок во флете, не торгуем</strong>
        </div>
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
                <tr>
                    <td><strong>STRONG</strong></td>
                    <td>все условия выполнены</td>
                </tr>
                <tr>
                    <td><strong>WEAK</strong></td>
                    <td>не выполнены условия (не торгуем)</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">📉 Стоп-лосс и тейк-профит (на основе уровней Ichimoku)</h2>
    <div class="section-content">
        <p>Ichimoku сам даёт уровни, не заменяем их полностью на ATR</p>
        <h3>BUY:</h3>
        <div class="code-block">
            SL = min(Kijun, Senkou B) (ниже Kijun или Senkou B, что дальше)<br>
            Risk = Цена - SL<br>
            TP = Цена + (Risk × 2.0)
        </div>
        <h3>SELL:</h3>
        <div class="code-block">
            SL = max(Kijun, Senkou B) (выше Kijun или Senkou B, что дальше)<br>
            Risk = SL - Цена<br>
            TP = Цена - (Risk × 2.0)
        </div>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">🔥 Итоговая рабочая версия</h2>
    <div class="section-content">
        <h3>BUY:</h3>
        <ol>
            <li>Цена > облака</li>
            <li>Tenkan > Kijun</li>
            <li>Chikou > цена(-26)</li>
            <li>RSI 45–65</li>
            <li>Цена ≤ 1 ATR от облака</li>
            <li>Облако не плоское (|Senkou A - Senkou B| ≥ 0.5×ATR)</li>
        </ol>
        <h3>SELL:</h3>
        <ol>
            <li>Цена < облака</li>
            <li>Tenkan < Kijun</li>
            <li>Chikou < цена(-26)</li>
            <li>RSI 35–55</li>
            <li>Цена ≤ 1 ATR от облака</li>
            <li>Облако не плоское (|Senkou A - Senkou B| ≥ 0.5×ATR)</li>
        </ol>
    </div>
</div>


