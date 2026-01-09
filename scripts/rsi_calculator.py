#!/usr/bin/env python3
"""
RSI Calculator for Cryptocurrency Data
This script fetches data from Binance API and calculates RSI for all symbols
"""

import requests
import pandas as pd
import json
import time
from typing import List, Dict, Optional

class RSICalculator:
    def __init__(self):
        self.base_url = "https://fapi.binance.com/fapi/v1/klines"
        self.symbols = [
            '0G', '1000CAT', '1000CHEEMS', '1000SATS', '1INCH', '1INCHDOWN', '1INCHUP',
            '1MBABYDOGE', 'A', 'A2Z', 'AAVE', 'AAVEDOWN', 'AAVEUP', 'ACA', 'ACE', 'ACH',
            'ACM', 'ACT', 'ACX', 'ADA', 'ADADOWN', 'ADAUP', 'ADX', 'AE', 'AERGO', 'AEUR',
            'AEVO', 'AGI', 'AGIX', 'AGLD', 'AI', 'AION', 'AIXBT', 'AKRO', 'ALCX', 'ALGO',
            'ALICE', 'ALPACA', 'ALPHA', 'ALPINE', 'ALT', 'AMB', 'AMP', 'ANC', 'ANIME',
            'ANKR', 'ANT', 'ANY', 'APE', 'API3', 'APPC', 'APT', 'AR', 'ARB', 'ARDR',
            'ARK', 'ARKM', 'ARN', 'ARPA', 'ARS', 'ASR', 'AST', 'ASTR', 'ATA', 'ATM',
            'ATOM', 'AUCTION', 'AUD', 'AUDIO', 'AUTO', 'AVA', 'AVAX', 'AVNT', 'AWE',
            'AXL', 'AXS', 'BABY', 'BADGER', 'BAKE', 'BAL', 'BANANA', 'BANANAS31', 'BAND',
            'BAR', 'BARD', 'BAT', 'BB', 'BCC', 'BCD', 'BCH', 'BCHA', 'BCHABC', 'BCHDOWN',
            'BCHSV', 'BCHUP', 'BCN', 'BCPT', 'BDOT', 'BEAM', 'BEAMX', 'BEAR', 'BEL',
            'BERA', 'BETA', 'BETH', 'BFUSD', 'BGBP', 'BICO', 'BIDR', 'BIFI', 'BIGTIME',
            'BIO', 'BKRW', 'BLUR', 'BLZ', 'BMT', 'BNB', 'BNBBEAR', 'BNBBULL', 'BNBDOWN',
            'BNBUP', 'BNSOL', 'BNT', 'BNX', 'BOME', 'BOND', 'BONK', 'BOT', 'BQX', 'BRD',
            'BRL', 'BROCCOLI714', 'BSW', 'BTC', 'BTCB', 'BTCDOWN', 'BTCST', 'BTCUP',
            'BTG', 'BTS', 'BTT', 'BTTC', 'BULL', 'BURGER', 'BUSD', 'BVND', 'BZRX', 'C',
            'C98', 'CAKE', 'CATI', 'CDT', 'CELO', 'CELR', 'CETUS', 'CFX', 'CGPT', 'CHAT',
            'CHESS', 'CHR', 'CHZ', 'CITY', 'CKB', 'CLOAK', 'CLV', 'CMT', 'CND', 'COCOS',
            'COMBO', 'COMP', 'COOKIE', 'COP', 'COS', 'COTI', 'COVER', 'COW', 'CREAM',
            'CRV', 'CTK', 'CTSI', 'CTXC', 'CVC', 'CVP', 'CVX', 'CYBER', 'CZK', 'D',
            'DAI', 'DAR', 'DASH', 'DATA', 'DCR', 'DEGO', 'DENT', 'DEXE', 'DF', 'DGB',
            'DGD', 'DIA', 'DLT', 'DNT', 'DOCK', 'DODO', 'DOGE', 'DOGS', 'DOLO', 'DOT',
            'DOTDOWN', 'DOTUP', 'DREP', 'DUSK', 'DYDX', 'DYM', 'EASY', 'EDEN', 'EDO',
            'EDU', 'EGLD', 'EIGEN', 'ELF', 'ENA', 'ENG', 'ENJ', 'ENS', 'EOS', 'EOSBEAR',
            'EOSBULL', 'EOSDOWN', 'EOSUP', 'EPIC', 'EPS', 'EPX', 'ERA', 'ERD', 'ERN',
            'ETC', 'ETH', 'ETHBEAR', 'ETHBULL', 'ETHDOWN', 'ETHFI', 'ETHUP', 'EUR',
            'EURI', 'EVX', 'EZ', 'FARM', 'FDUSD', 'FET', 'FF', 'FIDA', 'FIL', 'FILDOWN',
            'FILUP', 'FIO', 'FIRO', 'FIS', 'FLM', 'FLOKI', 'FLOW', 'FLUX', 'FOR', 'FORM',
            'FORTH', 'FRONT', 'FTM', 'FTT', 'FUEL', 'FUN', 'FXS', 'G', 'GAL', 'GALA',
            'GAS', 'GBP', 'GFT', 'GHST', 'GLM', 'GLMR', 'GMT', 'GMX', 'GNO', 'GNS',
            'GNT', 'GO', 'GPS', 'GRS', 'GRT', 'GTC', 'GTO', 'GUN', 'GVT', 'GXS', 'HAEDAL',
            'HARD', 'HBAR', 'HC', 'HEGIC', 'HEI', 'HEMI', 'HFT', 'HIFI', 'HIGH', 'HIVE',
            'HMSTR', 'HNT', 'HOLO', 'HOME', 'HOOK', 'HOT', 'HSR', 'HUMA', 'HYPER', 'ICN',
            'ICP', 'ICX', 'ID', 'IDEX', 'IDRT', 'ILV', 'IMX', 'INIT', 'INJ', 'INS', 'IO',
            'IOST', 'IOTA', 'IOTX', 'IQ', 'IRIS', 'JASMY', 'JOE', 'JPY', 'JST', 'JTO',
            'JUP', 'JUV', 'KAIA', 'KAITO', 'KAVA', 'KDA', 'KEEP', 'KERNEL', 'KEY',
            'KLAY', 'KMD', 'KMNO', 'KNC', 'KP3R', 'KSM', 'LA', 'LAYER', 'LAZIO', 'LDO',
            'LEND', 'LEVER', 'LINA', 'LINEA', 'LINK', 'LINKDOWN', 'LINKUP', 'LISTA',
            'LIT', 'LOKA', 'LOOM', 'LPT', 'LQTY', 'LRC', 'LSK', 'LTC', 'LTCDOWN',
            'LTCUP', 'LTO', 'LUMIA', 'LUN', 'LUNA', 'LUNC', 'MAGIC', 'MANA', 'MANTA',
            'MASK', 'MATIC', 'MAV', 'MBL', 'MBOX', 'MC', 'MCO', 'MDA', 'MDT', 'MDX',
            'ME', 'MEME', 'METIS', 'MFT', 'MINA', 'MIR', 'MIRA', 'MITH', 'MITO', 'MKR',
            'MLN', 'MOB', 'MOD', 'MOVE', 'MOVR', 'MTH', 'MTL', 'MUBARAK', 'MULTI',
            'MXN', 'NANO', 'NAS', 'NAV', 'NBS', 'NCASH', 'NEAR', 'NEBL', 'NEIRO', 'NEO',
            'NEWT', 'NEXO', 'NFP', 'NGN', 'NIL', 'NKN', 'NMR', 'NOT', 'NPXS', 'NTRN',
            'NU', 'NULS', 'NXPC', 'NXS', 'OAX', 'OCEAN', 'OG', 'OGN', 'OM', 'OMG',
            'OMNI', 'ONDO', 'ONE', 'ONG', 'ONT', 'OOKI', 'OP', 'OPEN', 'ORCA', 'ORDI',
            'ORN', 'OSMO', 'OST', 'OXT', 'PARTI', 'PAX', 'PAXG', 'PDA', 'PENDLE',
            'PENGU', 'PEOPLE', 'PEPE', 'PERL', 'PERP', 'PHA', 'PHB', 'PHX', 'PIVX',
            'PIXEL', 'PLA', 'PLN', 'PLUME', 'PNT', 'PNUT', 'POA', 'POE', 'POL', 'POLS',
            'POLY', 'POLYX', 'POND', 'PORTAL', 'PORTO', 'POWR', 'PPT', 'PROM', 'PROS',
            'PROVE', 'PSG', 'PUMP', 'PUNDIX', 'PYR', 'PYTH', 'QI', 'QKC', 'QLC', 'QNT',
            'QSP', 'QTUM', 'QUICK', 'RAD', 'RAMP', 'RARE', 'RAY', 'RCN', 'RDN', 'RDNT',
            'RED', 'REEF', 'REI', 'REN', 'RENBTC', 'RENDER', 'REP', 'REQ', 'RESOLV',
            'REZ', 'RGT', 'RIF', 'RLC', 'RNDR', 'RON', 'RONIN', 'ROSE', 'RPL', 'RPX',
            'RSR', 'RUB', 'RUNE', 'RVN', 'S', 'SAGA', 'SAHARA', 'SALT', 'SAND', 'SANTOS',
            'SC', 'SCR', 'SCRT', 'SEI', 'SFP', 'SHELL', 'SHIB', 'SIGN', 'SKL', 'SKY',
            'SKYCOIN', 'SLF', 'SLP', 'SNGLS', 'SNM', 'SNT', 'SNX', 'SOL', 'SOLV',
            'SOMI', 'SOPH', 'SPARTA', 'SPELL', 'SPK', 'SRM', 'SSV', 'STEEM', 'STG',
            'STMX', 'STO', 'STORJ', 'STORM', 'STPT', 'STRAT', 'STRAX', 'STRK', 'STX',
            'SUB', 'SUI', 'SUN', 'SUPER', 'SUSD', 'SUSHI', 'SUSHIDOWN', 'SUSHIUP',
            'SWRV', 'SXP', 'SXPDOWN', 'SXPUP', 'SXT', 'SYN', 'SYRUP', 'SYS', 'T',
            'TAO', 'TCT', 'TFUEL', 'THE', 'THETA', 'TIA', 'TKO', 'TLM', 'TNB', 'TNSR',
            'TNT', 'TOMO', 'TON', 'TORN', 'TOWNS', 'TRB', 'TREE', 'TRIBE', 'TRIG',
            'TROY', 'TRU', 'TRUMP', 'TRX', 'TRXDOWN', 'TRXUP', 'TRY', 'TST', 'TURBO',
            'TUSD', 'TUSDB', 'TUT', 'TVK', 'TWT', 'UAH', 'UFT', 'UMA', 'UNFI', 'UNI',
            'UNIDOWN', 'UNIUP', 'USD1', 'USDC', 'USDE', 'USDP', 'USDS', 'USDSB',
            'USDT', 'UST', 'USTC', 'USUAL', 'UTK', 'VAI', 'VANA', 'VANRY', 'VELODROME',
            'VEN', 'VET', 'VGX', 'VIA', 'VIB', 'VIBE', 'VIC', 'VIDT', 'VIRTUAL', 'VITE',
            'VOXEL', 'VTHO', 'W', 'WABI', 'WAN', 'WAVES', 'WAXP', 'WBETH', 'WBTC',
            'WCT', 'WIF', 'WIN', 'WING', 'WINGS', 'WLD', 'WLFI', 'WNXM', 'WOO', 'WPR',
            'WRX', 'WTC', 'XAI', 'XEC', 'XEM', 'XLM', 'XLMDOWN', 'XLMUP', 'XMR', 'XNO',
            'XPL', 'XRP', 'XRPBEAR', 'XRPBULL', 'XRPDOWN', 'XRPUP', 'XTZ', 'XTZDOWN',
            'XTZUP', 'XUSD', 'XVG', 'XVS', 'XZC', 'YFI', 'YFIDOWN', 'YFII', 'YFIUP',
            'YGG', 'YOYO', 'ZAR', 'ZEC', 'ZEN', 'ZIL', 'ZK', 'ZKC', 'ZRO', 'ZRX'
        ]

    def fetch_klines_data(self, symbol: str, interval: str = '15m', limit: int = 30) -> Optional[List]:
        """Fetch klines data from Binance API"""
        try:
            url = f"{self.base_url}?symbol={symbol}USDT&interval={interval}&limit={limit}"
            response = requests.get(url, timeout=10)
            response.raise_for_status()
            return response.json()
        except requests.exceptions.RequestException as e:
            print(f"Error fetching data for {symbol}: {e}")
            return None

    def calculate_rsi(self, klines: List) -> Optional[float]:
        """Calculate RSI using pandas"""
        if not klines or len(klines) < 15:
            return None

        # Extract close prices
        closes = [float(candle[4]) for candle in klines]

        # Create DataFrame
        df = pd.DataFrame(closes, columns=["Close"])

        # Period RSI
        n = 14

        # Price differences
        delta = df["Close"].diff()

        # Gains and losses
        gain = delta.clip(lower=0)
        loss = -delta.clip(upper=0)

        # Rolling average Gain/Loss
        avg_gain = gain.rolling(n).mean()
        avg_loss = loss.rolling(n).mean()

        # RS and RSI
        rs = avg_gain / avg_loss
        df["RSI"] = 100 - (100 / (1 + rs))

        # Return the last RSI value
        return round(df["RSI"].iloc[-1], 2) if not df["RSI"].isna().iloc[-1] else None

    def get_rsi_for_symbol(self, symbol: str) -> Optional[float]:
        """Get RSI for a specific symbol"""
        klines = self.fetch_klines_data(symbol)
        if klines:
            return self.calculate_rsi(klines)
        return None

    def get_all_rsi(self) -> Dict[str, float]:
        """Get RSI for all symbols"""
        rsi_data = {}
        errors = {}

        for symbol in self.symbols:
            try:
                rsi = self.get_rsi_for_symbol(symbol)
                if rsi is not None:
                    rsi_data[symbol] = rsi
                else:
                    errors[symbol] = "No data available"
            except Exception as e:
                errors[symbol] = str(e)
                print(f"Error calculating RSI for {symbol}: {e}")

            # Add small delay to avoid rate limiting
            time.sleep(0.1)

        return {
            'rsi_data': rsi_data,
            'errors': errors,
            'total_symbols': len(self.symbols),
            'successful_calculations': len(rsi_data),
            'failed_calculations': len(errors)
        }

    def save_to_json(self, data: Dict, filename: str = 'rsi_data.json'):
        """Save RSI data to JSON file"""
        with open(filename, 'w') as f:
            json.dump(data, f, indent=2)
        print(f"RSI data saved to {filename}")

if __name__ == "__main__":
    calculator = RSICalculator()
    
    print("Calculating RSI for all cryptocurrency symbols...")
    result = calculator.get_all_rsi()
    
    print(f"\nResults:")
    print(f"Total symbols: {result['total_symbols']}")
    print(f"Successful calculations: {result['successful_calculations']}")
    print(f"Failed calculations: {result['failed_calculations']}")
    
    # Save to file
    calculator.save_to_json(result)
    
    # Print first 10 RSI values as example
    print(f"\nFirst 10 RSI values:")
    for i, (symbol, rsi) in enumerate(list(result['rsi_data'].items())[:10]):
        print(f"{symbol}: {rsi}")
