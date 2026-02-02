<div class="content-section">
    <h2 class="section-title">🔑 Ключевая идея</h2>
    <div class="section-content">
        <p>MTF не ищет идеальную точку. Её роль:</p>
        <ul>
            <li><strong>HTF (1h)</strong> → фильтр контекста (обязательный, не начисляет баллы)</li>
            <li><strong>15m</strong> → основной сигнал (балльная система)</li>
            <li><strong>5m</strong> → триггер входа, а не фильтр (+10 баллов максимум)</li>
        </ul>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">1️⃣ HTF (1h) — ТОЛЬКО ФИЛЬТР</h2>
    <div class="section-content">
        <div class="warning-box">
            <strong>❌ Не начисляет баллы</strong>
        </div>
        <h3>BUY разрешён, если:</h3>
        <ul>
            <li>Цена ≥ EMA50(1h) <strong>ИЛИ</strong></li>
            <li>RSI(1h) ≥ 45</li>
        </ul>
        <h3>SELL разрешён, если:</h3>
        <ul>
            <li>Цена ≤ EMA50(1h) <strong>ИЛИ</strong></li>
            <li>RSI(1h) ≤ 55</li>
        </ul>
        <p>📌 HTF не обязан быть идеальным, он просто "не против" сделки</p>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">2️⃣ Основная логика — 15m (балльная система)</h2>
    <div class="section-content">
        <h3>BUY (макс ~100 баллов):</h3>
        <table>
            <thead>
                <tr>
                    <th>Условие</th>
                    <th>Баллы</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>RSI(15m) ≤ 35</td><td>+25</td></tr>
                <tr><td>RSI(15m) 35–45</td><td>+15</td></tr>
                <tr><td>Цена ≤ нижняя BB × 1.01</td><td>+25</td></tr>
                <tr><td>BB position ≤ 25%</td><td>+15</td></tr>
                <tr><td>Цена выше EMA50(15m)</td><td>+15</td></tr>
                <tr><td>EMA20 > EMA50</td><td>+10</td></tr>
                <tr><td>Bullish candle (engulf/pin)</td><td>+10</td></tr>
            </tbody>
        </table>
        <h3>SELL (зеркально):</h3>
        <table>
            <thead>
                <tr>
                    <th>Условие</th>
                    <th>Баллы</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>RSI(15m) ≥ 65</td><td>+25</td></tr>
                <tr><td>RSI(15m) 55–65</td><td>+15</td></tr>
                <tr><td>Цена ≥ верхняя BB × 0.99</td><td>+25</td></tr>
                <tr><td>BB position ≥ 75%</td><td>+15</td></tr>
                <tr><td>Цена ниже EMA50(15m)</td><td>+15</td></tr>
                <tr><td>EMA20 < EMA50</td><td>+10</td></tr>
                <tr><td>Bearish candle</td><td>+10</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">3️⃣ LTF (5m) — ТРИГГЕР, не фильтр</h2>
    <div class="section-content">
        <div class="warning-box">
            <strong>❌ Не блокирует сигнал</strong>
        </div>
        <h3>Для BUY (любое одно, +10 баллов максимум):</h3>
        <ul>
            <li>RSI(5m) пересёк 30 снизу <strong>ИЛИ</strong></li>
            <li>Bullish candle <strong>ИЛИ</strong></li>
            <li>EMA9 > EMA21</li>
        </ul>
        <h3>Для SELL (любое одно, +10 баллов максимум):</h3>
        <ul>
            <li>RSI(5m) пересёк 70 сверху <strong>ИЛИ</strong></li>
            <li>Bearish candle <strong>ИЛИ</strong></li>
            <li>EMA9 < EMA21</li>
        </ul>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">4️⃣ Генерация сигнала</h2>
    <div class="section-content">
        <table>
            <thead>
                <tr>
                    <th>Сила</th>
                    <th>Баллы</th>
                    <th>Обязательные условия</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>STRONG</strong></td>
                    <td>≥ 70</td>
                    <td>+ Bullish/Bearish candle <strong>ИЛИ</strong> EMA20 >/< EMA50</td>
                </tr>
                <tr>
                    <td><strong>MEDIUM</strong></td>
                    <td>55–69</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td><strong>WEAK</strong></td>
                    <td>45–54</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td><strong>HOLD</strong></td>
                    <td>&lt; 45</td>
                    <td>-</td>
                </tr>
            </tbody>
        </table>
        <div class="highlight-box">
            <strong>Важно:</strong> STRONG возможен только если есть хотя бы одно подтверждение:
            <ul style="margin-top: 8px;">
                <li>Bullish/Bearish candle (engulfing или pin bar) <strong>ИЛИ</strong></li>
                <li>EMA20 > EMA50 (для BUY) / EMA20 < EMA50 (для SELL)</li>
            </ul>
            <p style="margin-top: 8px;">Если 70+ баллов, но нет подтверждения → максимум MEDIUM.</p>
        </div>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">🔧 Volatility фильтр</h2>
    <div class="section-content">
        <div class="code-block">
            ATR% < 0.35% → HOLD
        </div>
        <p>Это убирает флет, снижает шум, но не убивает частоту сигналов.</p>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">📉 Стоп-лосс и тейк-профит</h2>
    <div class="section-content">
        <h3>Параметры:</h3>
        <ul>
            <li>SL = ATR × 2.0</li>
            <li>TP = ATR × 2.5</li>
        </ul>
        <h3>BUY:</h3>
        <div class="code-block">
            SL = min(Цена - ATR×2.0, Нижняя BB × 0.98)<br>
            TP = Цена + ATR×2.5
        </div>
        <h3>SELL:</h3>
        <div class="code-block">
            SL = max(Цена + ATR×2.0, Верхняя BB × 1.02)<br>
            TP = Цена - ATR×2.5
        </div>
    </div>
</div>


