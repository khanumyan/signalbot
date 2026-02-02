<div class="content-section">
    <h2 class="section-title">🔥 Новая "боевая" логика</h2>
    <div class="section-content">
        <p>Скальпинговая стратегия для быстрой торговли на таймфрейме 5m. Использует строгие условия без балльной системы.</p>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">🔑 3 ключевых блока логики</h2>
    <div class="section-content">
        <h3>Блок 1 — НАПРАВЛЕНИЕ (обязательный)</h3>
        <ul>
            <li><strong>BUY:</strong> EMA9 > EMA21</li>
            <li><strong>SELL:</strong> EMA9 < EMA21</li>
        </ul>
        <div class="warning-box">
            <strong>❗ Без этого сигнала сделку не открываем вообще</strong>
        </div>

        <h3>Блок 2 — ТАЙМИНГ (вход)</h3>
        <ul>
            <li><strong>BUY:</strong> K пересекает D СНИЗУ (только момент пересечения)</li>
            <li><strong>SELL:</strong> K пересекает D СВЕРХУ (только момент пересечения)</li>
        </ul>
        <div class="highlight-box">
            <strong>📌 ВАЖНО:</strong> учитывать только момент пересечения, не "K > D уже 10 свечей"
        </div>

        <h3>Блок 3 — ЗОНА STOCHASTIC</h3>
        <ul>
            <li><strong>BUY:</strong> K ∈ [20–50]</li>
            <li><strong>SELL:</strong> K ∈ [50–80]</li>
        </ul>
        <div class="warning-box">
            <strong>❌ НЕ входить:</strong>
            <ul style="margin-top: 8px;">
                <li>BUY при K > 60</li>
                <li>SELL при K < 40</li>
            </ul>
        </div>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">💪 Определение силы сигнала</h2>
    <div class="section-content">
        <p>Сила определяется на основе импульса: <strong>Δ = |K − D|</strong></p>
        <table>
            <thead>
                <tr>
                    <th>Сила</th>
                    <th>Условие</th>
                    <th>TP Multiplier</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>STRONG</strong></td>
                    <td>Δ = |K − D| ≥ 7</td>
                    <td>2.4</td>
                </tr>
                <tr>
                    <td><strong>MEDIUM</strong></td>
                    <td>Δ = |K − D| ∈ [3–6]</td>
                    <td>1.8</td>
                </tr>
                <tr>
                    <td><strong>WEAK</strong></td>
                    <td>Δ = |K − D| < 3</td>
                    <td>❌ НЕ ТОРГУЕМ</td>
                </tr>
            </tbody>
        </table>
        <div class="warning-box">
            <strong>👉 WEAK убираем полностью — это мусор для 5m</strong>
        </div>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">🔧 Фильтры (обязательные)</h2>
    <div class="section-content">
        <h3>1. ATR-фильтр:</h3>
        <div class="code-block">
            ATR(14) > SMA(ATR, 20)
        </div>
        <div class="warning-box">
            <strong>❗ Если волатильности нет — НЕ ТОРГУЕМ</strong>
        </div>

        <h3>2. Защита от флет-пилы:</h3>
        <div class="code-block">
            |EMA9 − EMA21| ≥ 0.1 × ATR
        </div>
        <div class="warning-box">
            <strong>❌ Если EMA слишком близко — не входить</strong>
        </div>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">📉 Стоп-лосс и тейк-профит</h2>
    <div class="section-content">
        <h3>SL (всегда):</h3>
        <div class="code-block">
            SL = ATR × 1.5
        </div>
        <h3>TP (зависит от силы):</h3>
        <ul>
            <li><strong>STRONG:</strong> TP = ATR × 2.4</li>
            <li><strong>MEDIUM:</strong> TP = ATR × 1.8</li>
        </ul>
        <div class="success-box">
            <strong>🎯 Это даёт положительное матожидание</strong>
        </div>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">🔥 Итоговая "боевая" логика</h2>
    <div class="section-content">
        <h3>BUY:</h3>
        <ol>
            <li>EMA9 > EMA21</li>
            <li>EMA distance ≥ 0.1 × ATR</li>
            <li>ATR > SMA(ATR,20)</li>
            <li>K crosses D from below</li>
            <li>K ∈ [20–50]</li>
            <li>|K − D| ≥ 3</li>
        </ol>
        <h3>SELL:</h3>
        <ol>
            <li>EMA9 < EMA21</li>
            <li>EMA distance ≥ 0.1 × ATR</li>
            <li>ATR > SMA(ATR,20)</li>
            <li>K crosses D from above</li>
            <li>K ∈ [50–80]</li>
            <li>|K − D| ≥ 3</li>
        </ol>
    </div>
</div>



