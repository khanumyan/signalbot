<?php

namespace App\Console\Commands;

use App\Models\CryptoNews;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchCryptoNewsCommand extends Command
{
    protected $signature = 'crypto:fetch-news';
    protected $description = 'Fetch crypto news from newsdata.io API and send to Telegram';

    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }

    public function handle()
    {
        $this->info('🔄 Fetching crypto news...');

        try {
            $apiKey = 'pub_982554f4728846ee996752dadf730ff9';
            $url = "https://newsdata.io/api/1/crypto?apikey={$apiKey}&language=ru";

            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                $this->error('❌ Failed to fetch news: ' . $response->status());
                Log::error('Crypto news API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return Command::FAILURE;
            }

            $data = $response->json();

            if (!isset($data['results']) || !is_array($data['results'])) {
                $this->warn('⚠️ No results found in API response');
                return Command::SUCCESS;
            }

            $newArticles = 0;
            $sentToTelegram = 0;
            $skippedBlacklisted = 0;

            foreach ($data['results'] as $article) {
                if (empty($article['article_id'])) {
                    continue;
                }

                // Check if article already exists
                $exists = CryptoNews::where('article_id', $article['article_id'])->exists();

                if ($exists) {
                    continue; // Skip existing articles
                }

                // Parse pub_date
                $pubDate = null;
                if (isset($article['pubDate'])) {
                    try {
                        $pubDate = \Carbon\Carbon::parse($article['pubDate']);
                    } catch (\Exception $e) {
                        $pubDate = now();
                    }
                } else {
                    $pubDate = now();
                }

                // Save new article
                $cryptoNews = CryptoNews::create([
                    'article_id' => $article['article_id'],
                    'title' => $article['title'] ?? '',
                    'description' => $article['description'] ?? null,
                    'link' => $article['link'] ?? '',
                    'pub_date' => $pubDate,
                    'creator' => $article['creator'] ?? null,
                    'coin' => $article['coin'] ?? null,
                    'image_url' => $article['image_url'] ?? null,
                    'source_name' => $article['source_name'] ?? null,
                    'source_id' => $article['source_id'] ?? null,
                    'keywords' => $article['keywords'] ?? null,
                    'content' => $article['content'] ?? null,
                    'language' => $article['language'] ?? null,
                    'sent_to_telegram' => false,
                ]);

                $newArticles++;

                // Проверяем черный список перед отправкой
                $blacklistedSources = config('crypto_news.blacklisted_sources', []);
                $sourceName = $cryptoNews->source_name ? trim($cryptoNews->source_name) : null;
                $isBlacklisted = false;
                
                if ($sourceName) {
                    foreach ($blacklistedSources as $blacklistedSource) {
                        if (strcasecmp($sourceName, trim($blacklistedSource)) === 0) {
                            $isBlacklisted = true;
                            $skippedBlacklisted++;
                            $cryptoNews->update(['sent_to_telegram' => false]); // Помечаем как не отправленную
                            break;
                        }
                    }
                }

                // Send to Telegram только если не в черном списке
                if (!$isBlacklisted && $this->telegramService->sendCryptoNews($cryptoNews)) {
                    $cryptoNews->update(['sent_to_telegram' => true]);
                    $sentToTelegram++;
                }
            }

            $this->info("✅ Processed: {$newArticles} new articles, {$sentToTelegram} sent to Telegram" . 
                       ($skippedBlacklisted > 0 ? ", {$skippedBlacklisted} skipped (blacklisted sources)" : ""));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('Crypto news fetch error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}
