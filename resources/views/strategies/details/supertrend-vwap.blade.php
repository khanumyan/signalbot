<div class="content-section">
    <h2 class="section-title">📊 Логика работы</h2>
    <div class="section-content">
        <p>Внутридневная трендовая стратегия, сочетающая индикатор SuperTrend для определения тренда и VWAP для справедливой цены.</p>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">🔧 Фильтры (обязательные)</h2>
    <div class="section-content">
        <ol>
            <li><strong>ADX ≥ 25</strong> И <strong>ATR волатильность ≥ 0.6%</strong> (иначе HOLD)</li>
            <li><strong>RSI фильтр:</strong>
                <ul>
                    <li>BUY: RSI ≤ 25</li>
                    <li>SELL: RSI ≥ 75</li>
                </ul>
            </li>
            <li><strong>HTF фильтр (для 15m):</strong> проверяется тренд на 1h
                <ul>
                    <li>BUY: HTF тренд не должен быть DOWN</li>
                    <li>SELL: HTF тренд не должен быть UP</li>
                </ul>
            </li>
            <li><strong>Минимальный общий балл:</strong> ≥ 85</li>
            <li><strong>Минимальная разница вероятностей:</strong> ≥ 25%</li>
        </ol>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">🟢 Условия для BUY сигналов</h2>
    <div class="section-content">
        <ul>
            <li>SuperTrend = UP (+30 баллов)</li>
            <li>Цена была ниже VWAP, теперь закрылась выше VWAP И минимум выше SuperTrend (+50 баллов)</li>
            <li>Или цена выше VWAP и минимум выше SuperTrend (+30 баллов)</li>
            <li>Или цена была ниже VWAP, теперь выше VWAP (+20 баллов)</li>
            <li>Цена > SuperTrend (+20 баллов)</li>
        </ul>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">🔴 Условия для SELL сигналов</h2>
    <div class="section-content">
        <ul>
            <li>SuperTrend = DOWN (+30 баллов)</li>
            <li>Цена была выше VWAP, теперь закрылась ниже VWAP И максимум ниже SuperTrend (+50 баллов)</li>
            <li>Или цена ниже VWAP и максимум ниже SuperTrend (+30 баллов)</li>
            <li>Или цена была выше VWAP, теперь ниже VWAP (+20 баллов)</li>
            <li>Цена < SuperTrend (+20 баллов)</li>
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
                <tr>
                    <td><strong>STRONG</strong></td>
                    <td>разница вероятностей > 20%
                        <ul style="margin-top: 8px;">
                            <li>На 15m/5m: всегда STRONG</li>
                            <li>На 1h/4h: разница > 30%</li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td><strong>MEDIUM</strong></td>
                    <td>разница вероятностей > 20% на 1h/4h (но ≤ 30%)</td>
                </tr>
                <tr>
                    <td><strong>WEAK</strong></td>
                    <td>отфильтровывается (не отправляется)</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="content-section">
    <h2 class="section-title">📉 Стоп-лосс и тейк-профит</h2>
    <div class="section-content">
        <h3>Параметры:</h3>
        <ul>
            <li>SL Multiplier: 1.8 (по умолчанию)</li>
            <li>TP Multiplier: 3.0 (по умолчанию) - улучшенное соотношение 1.8:3.0</li>
        </ul>
        <h3>BUY:</h3>
        <div class="code-block">
            SL = Цена - (ATR × 1.8)<br>
            TP = Цена + (ATR × 3.0)
        </div>
        <h3>SELL:</h3>
        <div class="code-block">
            SL = Цена + (ATR × 1.8)<br>
            TP = Цена - (ATR × 3.0)
        </div>
    </div>
</div>



