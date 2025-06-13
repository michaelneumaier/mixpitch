<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Project;

class RedditService
{
    private string $baseUrl = 'https://oauth.reddit.com';
    private string $authUrl = 'https://www.reddit.com/api/v1/access_token';
    
    public function getAccessToken(): string
    {
        // Cache token for 50 minutes (expires in 60)
        return Cache::remember('reddit_access_token', 3000, function () {
            $response = Http::withBasicAuth(
                config('services.reddit.client_id'),
                config('services.reddit.client_secret')
            )
            ->withUserAgent(config('services.reddit.user_agent'))
            ->asForm()
            ->post($this->authUrl, [
                'grant_type' => 'password',
                'username' => config('services.reddit.username'),
                'password' => config('services.reddit.password'),
            ]);

            if (!$response->successful()) {
                Log::error('Reddit API authentication failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Reddit authentication failed');
            }

            $data = $response->json();
            return $data['access_token'] ?? throw new \Exception('No access token received');
        });
    }
    
    public function submitProject(Project $project): array
    {
        $token = $this->getAccessToken();
        
        $title = $this->formatTitle($project);
        $text = $this->formatText($project);
        
        $response = Http::withHeaders([
            'Authorization' => "bearer {$token}",
            'User-Agent' => config('services.reddit.user_agent'),
        ])
        ->asForm()
        ->post("{$this->baseUrl}/api/submit", [
            'sr' => 'MixPitch',
            'kind' => 'self', // Text post instead of link post
            'title' => $title,
            'text' => $text,
            'resubmit' => false, // Prevent duplicate submissions
            'nsfw' => false,
            'spoiler' => false,
        ]);

        if (!$response->successful()) {
            Log::error('Reddit submission failed', [
                'project_id' => $project->id,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Reddit submission failed: ' . $response->body());
        }

        $responseData = $response->json();
        
        // Log the full Reddit API response for debugging
        Log::info('Reddit API response', [
            'project_id' => $project->id,
            'response' => $responseData
        ]);
        
        // Check if Reddit returned errors in the response
        if (isset($responseData['json']['errors']) && !empty($responseData['json']['errors'])) {
            $errors = $responseData['json']['errors'];
            Log::error('Reddit API returned errors', [
                'project_id' => $project->id,
                'errors' => $errors
            ]);
            throw new \Exception('Reddit API errors: ' . json_encode($errors));
        }

        // Parse Reddit's jQuery response to extract post information
        return $this->parseRedditResponse($responseData);
    }
    
    private function formatTitle(Project $project): string
    {
        $emoji = $project->isContest() ? '🏆' : '🎛️';
        $type = $project->isContest() ? 'Contest' : 'Project';
        
        $title = "{$emoji} {$type}: {$project->title}";
        
        // Add genre if available
        if ($project->genre) {
            $title .= " [{$project->genre}]";
        }
        
        // Add budget/prize info
        if ($project->isContest() && $project->hasPrizes()) {
            $totalPrizeValue = $project->getTotalPrizeBudget();
            if ($totalPrizeValue > 0) {
                $title .= " [\${$totalPrizeValue} in Prizes]";
            }
        } elseif ($project->budget > 0) {
            $title .= " [\${$project->budget} {$project->currency}]";
        }
        
        // Ensure title is under 300 characters
        if (strlen($title) > 300) {
            $title = substr($title, 0, 297) . '...';
        }
        
        return $title;
    }
    
    private function formatText(Project $project): string
    {
        $text = "{$project->description}\n\n";
        
        // Add project notes if available
        if (!empty($project->notes)) {
            $text .= "**📝 Additional Notes:**\n";
            $text .= "{$project->notes}\n\n";
        }
        
        // Add project details section
        $text .= "**📋 " . ($project->isContest() ? 'Contest' : 'Project') . " Details:**\n";
        
        if ($project->genre) {
            $text .= "• **Genre:** {$project->genre}\n";
        }
        
        // Format budget/prizes based on project type
        if ($project->isContest()) {
            $text .= $this->formatContestPrizes($project);
            $text .= $this->formatContestDeadlines($project);
        } else {
            if ($project->budget > 0) {
                $text .= "• **Budget:** \${$project->budget} {$project->currency}\n";
            } else {
                $text .= "• **Budget:** Collaboration/Credit only\n";
            }
            
            if ($project->deadline) {
                $text .= "• **Deadline:** {$project->deadline->format('M j, Y')}\n";
            }
        }
        
        // Add collaboration types if available
        if ($project->collaboration_type && is_array($project->collaboration_type) && !empty($project->collaboration_type)) {
            $lookingFor = $project->isContest() ? 'Contest Entries from' : 'Looking for';
            $text .= "• **{$lookingFor}:** " . implode(', ', $project->collaboration_type) . "\n";
        }
        
        // Add MixPitch info section
        $text .= "\n**🎵 About MixPitch:**\n";
        $text .= "MixPitch is a platform connecting artists with professional producers, mixing engineers, and other music professionals. Artists post projects describing their vision, and qualified professionals submit personalized pitches with demos.\n\n";
        
        // How to submit section (different for contests vs projects)
        if ($project->isContest()) {
            $text .= "**🏆 How to Enter the Contest:**\n";
            $text .= "1. **Visit the contest page** using the link below\n";
            $text .= "2. **Create a free account** or log in if you already have one\n";
            $text .= "3. **Submit your contest entry** with a demo showcasing your skills\n";
            $text .= "4. **Wait for judging** - winners will be announced after the judging deadline\n\n";
        } else {
            $text .= "**🚀 How to Submit a Pitch:**\n";
            $text .= "1. **Visit the project page** using the link below\n";
            $text .= "2. **Create a free account** or log in if you already have one\n";
            $text .= "3. **Submit your pitch** with a demo showcasing your style and approach\n";
            $text .= "4. **Collaborate directly** with the artist if selected\n\n";
        }
        
        // Add benefits
        $text .= "**✨ Why Use MixPitch:**\n";
        $text .= "• Direct communication between artists and professionals\n";
        $text .= "• Secure file sharing and project management\n";
        $text .= "• Portfolio building and networking opportunities\n";
        $text .= "• Fair compensation and clear project terms\n";
        if ($project->isContest()) {
            $text .= "• Transparent contest judging and winner announcements\n";
        }
        $text .= "\n";
        
        // Add r/MixPitch community section
        $text .= "**🤝 About r/MixPitch Community:**\n";
        $text .= "This subreddit is dedicated to music collaboration and professional networking. We welcome:\n";
        $text .= "• Artists seeking production, mixing, mastering, or other services\n";
        $text .= "• " . ($project->isContest() ? "Contest announcements and competition opportunities" : "Producers and engineers showcasing their skills and availability") . "\n";
        $text .= "• Constructive feedback and collaboration discussions\n";
        $text .= "• Success stories and project showcases\n\n";
        
        $text .= "**📝 Community Guidelines:**\n";
        $text .= "• **Be Professional:** Treat all interactions with respect and professionalism\n";
        $text .= "• **Quality Over Quantity:** Share meaningful projects and thoughtful pitches\n";
        $text .= "• **No Spam:** Avoid repetitive posts; focus on genuine collaboration\n";
        $text .= "• **Credit Where Due:** Always acknowledge collaborators and give proper credit\n";
        $text .= "• **Constructive Feedback:** Offer helpful, actionable advice when commenting\n\n";
        
        // Add call to action
        $projectUrl = route('projects.show', $project);
        $action = $project->isContest() ? 'Enter Contest' : 'Submit Your Pitch';
        $text .= "**🎯 Ready to " . strtolower($action) . "?** [View Full " . ($project->isContest() ? 'Contest' : 'Project') . " & {$action}]({$projectUrl})\n\n";
        
        // Add footer
        $text .= "*Posted via [MixPitch.com](https://mixpitch.com) - Where Music Collaboration Happens*";
        
        return $text;
    }
    
    private function formatContestPrizes(Project $project): string
    {
        $text = "";
        
        if ($project->hasPrizes()) {
            $prizeSummary = $project->getPrizeSummary();
            $text .= "• **Prizes:**\n";
            
            foreach ($prizeSummary as $prize) {
                $text .= "  - {$prize['emoji']} **{$prize['placement']}:** {$prize['display_value']}";
                if (!empty($prize['description'])) {
                    $text .= " - {$prize['description']}";
                }
                $text .= "\n";
            }
        } else {
            $text .= "• **Prizes:** Recognition and exposure\n";
        }
        
        return $text;
    }
    
    private function formatContestDeadlines(Project $project): string
    {
        $text = "";
        
        if ($project->submission_deadline) {
            $text .= "• **Submission Deadline:** {$project->submission_deadline->format('M j, Y \a\t g:i A T')}\n";
        }
        
        if ($project->judging_deadline) {
            $text .= "• **Judging Complete By:** {$project->judging_deadline->format('M j, Y')}\n";
        }
        
        return $text;
    }
    
    private function parseRedditResponse(array $responseData): array
    {
        // Initialize parsed data
        $parsedData = [
            'json' => [
                'data' => [
                    'id' => null,
                    'url' => null,
                    'permalink' => null
                ]
            ]
        ];
        
        // Reddit returns jQuery commands in the response
        // Look for redirect command which contains the post URL
        if (isset($responseData['jquery']) && is_array($responseData['jquery'])) {
            foreach ($responseData['jquery'] as $command) {
                if (is_array($command) && count($command) >= 3) {
                    // Look for redirect command: [1,10,"attr","redirect"] followed by URL
                    if ($command[2] === 'attr' && isset($command[3]) && $command[3] === 'redirect') {
                        continue; // This is just the redirect setup
                    }
                    
                    // Look for the actual redirect call with URL
                    if ($command[2] === 'call' && isset($command[3][0]) && is_string($command[3][0])) {
                        $url = $command[3][0];
                        
                        // Check if this looks like a Reddit post URL
                        if (strpos($url, 'reddit.com/r/') !== false && strpos($url, '/comments/') !== false) {
                            $parsedData['json']['data']['url'] = $url;
                            $parsedData['json']['data']['permalink'] = $url;
                            
                            // Extract post ID from URL: /comments/POST_ID/title/
                            if (preg_match('/\/comments\/([a-zA-Z0-9]+)\//', $url, $matches)) {
                                $parsedData['json']['data']['id'] = $matches[1];
                            }
                            
                            break;
                        }
                    }
                }
            }
        }
        
        Log::info('Parsed Reddit response', [
            'original_success' => $responseData['success'] ?? false,
            'parsed_data' => $parsedData
        ]);
        
        return $parsedData;
    }
} 