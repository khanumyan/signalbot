<?php

namespace App\Console\Commands;

use App\Models\CryptoNews;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Stichoza\GoogleTranslate\GoogleTranslate;

class FetchCryptoNewsEnCommand extends Command
{
    protected $signature = 'crypto:fetch-news-en';
    protected $description = 'Fetch crypto news from newsdata.io API (English) and translate to Russian, send to Telegram';

    protected TelegramService $telegramService;
    protected GoogleTranslate $translator;

    public function __construct(TelegramService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
        $this->translator = new GoogleTranslate();
        $this->translator->setSource('auto'); // Auto-detect source language
        $this->translator->setTarget('ru');   // Target language: Russian
    }

    public function handle()
    {
        $this->info('🔄 Fetching crypto news (English → Russian)...');

        try {
            $apiKey = 'pub_982554f4728846ee996752dadf730ff9';
            $url = "https://newsdata.io/api/1/crypto?apikey={$apiKey}&language=en";

            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                $this->error('❌ Failed to fetch news: ' . $response->status());
                Log::error('Crypto news API error (EN→RU)', [
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

                // Translate ALL texts to Russian
                $title = $this->translateToRussian($article['title'] ?? '');
                $description = isset($article['description']) ? $this->translateToRussian($article['description']) : null;
                $content = isset($article['content']) ? $this->translateToRussian($article['content']) : null;

                // Translate creator array to Russian
                $creator = null;
                if (isset($article['creator']) && is_array($article['creator'])) {
                    $creator = array_map(function($c) {
                        return $this->translateToRussian($c);
                    }, $article['creator']);
                }

                // Save new article with Russian language
                $cryptoNews = CryptoNews::create([
                    'article_id' => $article['article_id'],
                    'title' => $title,
                    'description' => $description,
                    'link' => $article['link'] ?? '',
                    'pub_date' => $pubDate,
                    'creator' => $creator,
                    'coin' => $article['coin'] ?? null,
                    'image_url' => $article['image_url'] ?? null,
                    'source_name' => $article['source_name'] ?? null,
                    'source_id' => $article['source_id'] ?? null,
                    'keywords' => $article['keywords'] ?? null,
                    'content' => $content,
                    'language' => 'ru', // Force Russian
                    'sent_to_telegram' => false,
                ]);

                $newArticles++;

                // Send to Telegram
                if ($this->telegramService->sendCryptoNews($cryptoNews)) {
                    $cryptoNews->update(['sent_to_telegram' => true]);
                    $sentToTelegram++;
                }
            }

            $this->info("✅ Processed: {$newArticles} new articles, {$sentToTelegram} sent to Telegram");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('Crypto news fetch error (EN→RU)', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Translate text to Russian using Stichoza\GoogleTranslate
     */
    private function translateToRussian(string $text): string
    {
        if (empty($text)) {
            return $text;
        }

        // Check if text is already in Russian
        if (preg_match('/[А-Яа-яЁё]/u', $text)) {
            return $text; // Already in Russian, no need to translate
        }

        try {
            // Limit text length for translation (Google has limits)
            $textToTranslate = mb_substr($text, 0, 5000);
            
            $translated = $this->translator->translate($textToTranslate);
            
            if ($translated && $translated !== $textToTranslate) {
                return $translated;
            }
        } catch (\Exception $e) {
            Log::debug("Translation failed for text: " . substr($text, 0, 50) . "... Error: " . $e->getMessage());
        }

        // If translation fails, return original text
        return $text;
    }
}

