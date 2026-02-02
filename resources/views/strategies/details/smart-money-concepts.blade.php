<div class="content-section">
    <h2 class="section-title">📊 Логика работы</h2>
    <div class="section-content">
        <p>Продвинутая стратегия на основе Order Blocks, Market Structure (BOS/CHOCH), Fair Value Gaps и зон ликвидности.</p>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">🔧 Обязательные условия</h2>
    <div class="section-content">
        <h3>1. Четкий тренд на H4:</h3>
        <ul>
            <li><strong>BULLISH:</strong> цена > EMA × 1.002 и RSI > 52</li>
            <li><strong>BEARISH:</strong> цена < EMA × 0.998 и RSI < 48</li>
            <li><strong>NEUTRAL:</strong> иначе (пропуск сигнала)</li>
        </ul>
        <h3>2. Возврат к Order Block</h3>
        <div class="warning-box">
            <strong>ОБЯЗАТЕЛЬНО</strong> - без возврата к OB сигнал не генерируется
        </div>
        <h3>3. Наличие ликвидности</h3>
        <div class="warning-box">
            <strong>ОБЯЗАТЕЛЬНО</strong> - рядом с Order Block должна быть зона ликвидности
        </div>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">🟢 Условия для BUY (бычий тренд)</h2>
    <div class="section-content">
        <table>
            <thead>
                <tr>
                    <th>Условие</th>
                    <th>Баллы</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>Возврат к Bullish Order Block</td><td>+50 <strong>(ОБЯЗАТЕЛЬНО)</strong></td></tr>
                <tr><td>Ликвидность ниже Order Block</td><td>+30 <strong>(ОБЯЗАТЕЛЬНО)</strong></td></tr>
                <tr><td>Подтверждение через BOS/CHOCH</td><td>+25</td></tr>
                <tr><td>Свечное подтверждение (бычья свеча)</td><td>+20</td></tr>
                <tr><td>Цена в Fair Value Gap</td><td>+15</td></tr>
                <tr><td>RSI в диапазоне 25-45</td><td>+10</td></tr>
                <tr><td><strong>Штраф:</strong> нет подтверждения структуры</td><td>-20</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">🔴 Условия для SELL (медвежий тренд)</h2>
    <div class="section-content">
        <table>
            <thead>
                <tr>
                    <th>Условие</th>
                    <th>Баллы</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>Возврат к Bearish Order Block</td><td>+50 <strong>(ОБЯЗАТЕЛЬНО)</strong></td></tr>
                <tr><td>Ликвидность выше Order Block</td><td>+30 <strong>(ОБЯЗАТЕЛЬНО)</strong></td></tr>
                <tr><td>Подтверждение через BOS/CHOCH</td><td>+25</td></tr>
                <tr><td>Свечное подтверждение (медвежья свеча)</td><td>+20</td></tr>
                <tr><td>Цена в Fair Value Gap</td><td>+15</td></tr>
                <tr><td>RSI в диапазоне 55-75</td><td>+10</td></tr>
                <tr><td><strong>Штраф:</strong> нет подтверждения структуры</td><td>-20</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">💪 Определение силы</h2>
    <div class="section-content">
        <table>
            <thead>
                <tr>
                    <th>Сила</th>
                    <th>Баллы</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>STRONG</strong></td>
                    <td>минимум 140 баллов</td>
                </tr>
                <tr>
                    <td><strong>MEDIUM</strong></td>
                    <td>минимум 120 баллов</td>
                </tr>
                <tr>
                    <td><strong>Минимум для сигнала</strong></td>
                    <td>100 баллов</td>
                </tr>
                <tr>
                    <td><strong>WEAK</strong></td>
                    <td>все остальное (не отправляется)</td>
                </tr>
            </tbody>
        </table>
        <div class="warning-box">
            <strong>Важно:</strong> Если !activeOrderBlock || !hasLiquidity → возвращаем HOLD
        </div>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">📉 Стоп-лосс и тейк-профит</h2>
    <div class="section-content">
        <p>Основаны на Order Blocks и зонах ликвидности</p>
        <h3>BUY:</h3>
        <div class="code-block">
            SL = Order Block Low × 0.995 (0.5% ниже OB)<br>
            Если есть ликвидность ниже OB: SL = Ликвидность × 0.995 (выбирается ближайшая)<br>
            TP = Цена + (ATR × 3.5) (базовый)<br>
            Если есть ликвидность выше: TP = Ликвидность × 0.998 (чуть ниже ликвидности)
        </div>
        <h3>SELL:</h3>
        <div class="code-block">
            SL = Order Block High × 1.005 (0.5% выше OB)<br>
            Если есть ликвидность выше OB: SL = Ликвидность × 1.005 (выбирается ближайшая)<br>
            TP = Цена - (ATR × 3.5) (базовый)<br>
            Если есть ликвидность ниже: TP = Ликвидность × 1.002 (чуть выше ликвидности)
        </div>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">🔍 Критерии Order Blocks</h2>
    <div class="section-content">
        <ul>
            <li>Тело свечи > 70% от диапазона свечи (было 60%)</li>
            <li>Объем > 2.0x среднего объема (было 1.8x)</li>
            <li>Подтверждение разворота минимум 2-3 свечами после OB</li>
        </ul>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">📊 Market Structure (BOS/CHOCH)</h2>
    <div class="section-content">
        <ul>
            <li>Проверяется в последних 15 свечах (не только текущая)</li>
            <li>Если BOS/CHOCH был в последних 10 свечах - считается актуальным</li>
        </ul>
        <div class="highlight-box">
            <strong>BOS (Break Of Structure)</strong> - пробой структуры<br>
            <strong>CHOCH (Change Of Character)</strong> - изменение характера движения
        </div>
    </div>
</div>



