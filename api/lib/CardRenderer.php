<?php

declare(strict_types=1);

namespace StumpVision;

use Imagick;
use ImagickDraw;
use ImagickPixel;
use ImagickException;
use RuntimeException;

require_once __DIR__ . '/Util.php';

/**
 * CardRenderer - Generate premium gradient share cards (FIXED VERSION)
 */
final class CardRenderer
{
    /**
     * Build premium gradient share card with modern design
     *
     * @param array<string, mixed> $match Match data
     * @param string $cardsDir Directory to save cards
     * @param string $baseName Base filename
     * @return array{0: array<int, string>, 1: string} Array of slide paths and cover path
     * @throws RuntimeException If Imagick is not available or rendering fails
     */
    public static function render(array $match, string $cardsDir, string $baseName): array
    {
        if (!extension_loaded('imagick')) {
            throw new RuntimeException('Imagick extension not available');
        }

        // Extract data safely with type checking
        $meta = [];
        if (isset($match['meta']) && is_array($match['meta'])) {
            $meta = $match['meta'];
        }

        $teams = [[], []];
        if (isset($match['teams']) && is_array($match['teams'])) {
            $teams = $match['teams'];
        }

        $inns = [];
        if (isset($match['innings']) && is_array($match['innings'])) {
            $inns = $match['innings'];
        }

        $teamAName = 'Team A';
        if (isset($teams[0]['name'])) {
            $teamAName = Util::safeStr($teams[0]['name'], 'Team A');
        }

        $teamBName = 'Team B';
        if (isset($teams[1]['name'])) {
            $teamBName = Util::safeStr($teams[1]['name'], 'Team B');
        }

        // Get balls per over from meta
        $ballsPerOver = 6;
        if (isset($meta['ballsPerOver'])) {
            $ballsPerOver = Util::safeInt($meta['ballsPerOver'], 6);
        }

        // Get scores
        [$aRuns, $aWkts, $aOvers] = self::summaryFor(0, $inns, $ballsPerOver);
        [$bRuns, $bWkts, $bOvers] = self::summaryFor(1, $inns, $ballsPerOver);

        // Determine winner
        $winner = self::determineWinner($aRuns, $bRuns, $aWkts, $bWkts, $teamAName, $teamBName);

        // Get top performers from BOTH innings
        $topBat = self::topBatterFromAllInnings($inns);
        $topBowl = self::topBowlerFromAllInnings($inns);

        $coverPng = $cardsDir . DIRECTORY_SEPARATOR . $baseName . '-card.png';

        try {
            // Create 1080x1920 card (Instagram Story size)
            $card = self::createGradientCanvas();
            
            // Add glassmorphism card overlay - TALLER to fill space
            self::addGlassCard($card, 60, 180, 960, 1600);
            
            // Header - StumpVision branding
            self::text($card, 'STUMPVISION', 540.0, 100.0, 32, '#ffffff', 'center', 800);
            self::text($card, 'MATCH SCORECARD', 540.0, 140.0, 20, 'rgba(255,255,255,0.7)', 'center', 400);
            
            // Team A Score - Large and prominent
            self::addTeamSection($card, $teamAName, $aRuns, $aWkts, $aOvers, 220);
            
            // VS divider with match info - MOVED UP
            self::text($card, 'VS', 540.0, 450.0, 28, 'rgba(255,255,255,0.5)', 'center', 600);
            $oversPerSide = 20;
            if (isset($meta['oversPerSide'])) {
                $oversPerSide = Util::safeInt($meta['oversPerSide'], 20);
            }
            $matchInfo = (string)$oversPerSide . ' OVERS PER SIDE';
            self::text($card, $matchInfo, 540.0, 490.0, 16, 'rgba(255,255,255,0.4)', 'center', 400);
            
            // Team B Score - MOVED UP
            self::addTeamSection($card, $teamBName, $bRuns, $bWkts, $bOvers, 560);
            
            // Winner banner with gradient - MOVED UP
            self::addWinnerBanner($card, $winner, 790);
            
            // Top Performers section - MOVED UP and LARGER
            self::addPerformersSection($card, $topBat, $topBowl, 920);
            
            // Footer - Clean and minimal
            self::text($card, 'Powered by StumpVision', 540.0, 1840.0, 18, 'rgba(255,255,255,0.4)', 'center', 400);
            
            // Save the card
            self::save($card, $coverPng);
            
            return [[$coverPng], $coverPng];
        } catch (ImagickException $e) {
            throw new RuntimeException('Card render error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create gradient canvas background
     *
     * @return Imagick Canvas with gradient
     * @throws ImagickException
     */
    private static function createGradientCanvas(): Imagick
    {
        $im = new Imagick();
        $im->newImage(1080, 1920, new ImagickPixel('transparent'));
        $im->setImageFormat('png');
        
        // Create modern blue-purple gradient
        $gradient = new Imagick();
        $gradient->newPseudoImage(1080, 1920, 'gradient:#0ea5e9-#7c3aed');
        
        // Add subtle noise texture for depth
        $gradient->addNoiseImage(Imagick::NOISE_GAUSSIAN, Imagick::CHANNEL_ALL);
        $gradient->blurImage(0, 1);
        
        $im->compositeImage($gradient, Imagick::COMPOSITE_OVER, 0, 0);
        $gradient->destroy();
        
        return $im;
    }

    /**
     * Add frosted glass card overlay
     *
     * @param Imagick $im Image object
     * @param int $x X position
     * @param int $y Y position
     * @param int $w Width
     * @param int $h Height
     * @return void
     * @throws ImagickException
     */
    private static function addGlassCard(Imagick $im, int $x, int $y, int $w, int $h): void
    {
        $glass = new ImagickDraw();
        $glass->setFillColor(new ImagickPixel('rgba(255, 255, 255, 0.1)'));
        $glass->setStrokeColor(new ImagickPixel('rgba(255, 255, 255, 0.2)'));
        $glass->setStrokeWidth(2);
        $glass->roundRectangle((float)$x, (float)$y, (float)($x + $w), (float)($y + $h), 40.0, 40.0);
        $im->drawImage($glass);
    }

    /**
     * Add team section with score
     *
     * @param Imagick $im Image object
     * @param string $team Team name
     * @param int $runs Runs scored
     * @param int $wkts Wickets lost
     * @param string $overs Overs bowled
     * @param int $y Y position
     * @return void
     * @throws ImagickException
     */
    private static function addTeamSection(Imagick $im, string $team, int $runs, int $wkts, string $overs, int $y): void
    {
        // Team name with subtle uppercase
        self::text($im, strtoupper($team), 540.0, (float)$y, 24, 'rgba(255,255,255,0.8)', 'center', 600);
        
        // Massive score display
        $scoreY = $y + 60;
        self::text($im, (string)$runs, 460.0, (float)$scoreY, 96, '#ffffff', 'right', 800);
        self::text($im, '/', 540.0, (float)$scoreY, 80, 'rgba(255,255,255,0.5)', 'center', 400);
        self::text($im, (string)$wkts, 620.0, (float)$scoreY, 96, '#ffffff', 'left', 800);
        
        // Overs info below score
        self::text($im, $overs . ' OVERS', 540.0, (float)($scoreY + 80), 20, 'rgba(255,255,255,0.6)', 'center', 400);
        
        // Add subtle divider line
        $divider = new ImagickDraw();
        $divider->setStrokeColor(new ImagickPixel('rgba(255,255,255,0.2)'));
        $divider->setStrokeWidth(2);
        $divider->line(240.0, (float)($y + 160), 840.0, (float)($y + 160));
        $im->drawImage($divider);
    }

    /**
     * Add winner banner
     *
     * @param Imagick $im Image object
     * @param string $winner Winner text
     * @param int $y Y position
     * @return void
     * @throws ImagickException
     */
    private static function addWinnerBanner(Imagick $im, string $winner, int $y): void
    {
        $box = new ImagickDraw();
        $box->setFillColor(new ImagickPixel('rgba(34, 211, 238, 0.2)'));
        $box->setStrokeColor(new ImagickPixel('rgba(34, 211, 238, 0.4)'));
        $box->setStrokeWidth(2);
        $box->roundRectangle(120.0, (float)$y, 960.0, (float)($y + 80), 20.0, 20.0);
        $im->drawImage($box);
        
        self::text($im, '🏆 ' . $winner, 540.0, (float)($y + 55), 28, '#22d3ee', 'center', 700);
    }

    /**
     * Add top performers section
     *
     * @param Imagick $im Image object
     * @param array<string, mixed> $topBat Top batter stats
     * @param array<string, mixed> $topBowl Top bowler stats
     * @param int $y Y position
     * @return void
     * @throws ImagickException
     */
    private static function addPerformersSection(Imagick $im, array $topBat, array $topBowl, int $y): void
    {
        self::text($im, 'TOP PERFORMERS', 540.0, (float)$y, 18, 'rgba(255,255,255,0.5)', 'center', 600);
        
        $batY = $y + 60;
        // LARGER cards with more vertical space
        self::addPerformerCard($im, 140, $batY, 380, 220, '🏏 BATTING', $topBat);
        self::addPerformerCard($im, 560, $batY, 380, 220, '⚡ BOWLING', $topBowl);
    }

    /**
     * Add individual performer card
     *
     * @param Imagick $im Image object
     * @param int $x X position
     * @param int $y Y position
     * @param int $w Width
     * @param int $h Height
     * @param string $label Label text
     * @param array<string, mixed> $stats Player stats
     * @return void
     * @throws ImagickException
     */
    private static function addPerformerCard(Imagick $im, int $x, int $y, int $w, int $h, string $label, array $stats): void
    {
        $card = new ImagickDraw();
        $card->setFillColor(new ImagickPixel('rgba(255, 255, 255, 0.08)'));
        $card->setStrokeColor(new ImagickPixel('rgba(255, 255, 255, 0.15)'));
        $card->setStrokeWidth(1);
        $card->roundRectangle((float)$x, (float)$y, (float)($x + $w), (float)($y + $h), 16.0, 16.0);
        $im->drawImage($card);
        
        $centerX = $x + ($w / 2);
        self::text($im, $label, (float)$centerX, (float)($y + 35), 14, 'rgba(255,255,255,0.5)', 'center', 600);
        
        $name = Util::safeStr($stats['name'] ?? '—', '—');
        self::text($im, $name, (float)$centerX, (float)($y + 75), 22, '#ffffff', 'center', 700);
        
        // Check if this is batting stats (has 'runs' key)
        if (isset($stats['runs'])) {
            // BATTING STATS
            $runs = Util::safeInt($stats['runs'], 0);
            $balls = Util::safeInt($stats['balls'], 0);
            $statLine = (string)$runs . ' (' . (string)$balls . ')';
            self::text($im, $statLine, (float)$centerX, (float)($y + 125), 32, '#22d3ee', 'center', 800);
            
            $fours = Util::safeInt($stats['fours'] ?? 0, 0);
            $sixes = Util::safeInt($stats['sixes'] ?? 0, 0);
            $boundaries = (string)$fours . '×4  ' . (string)$sixes . '×6';
            self::text($im, $boundaries, (float)$centerX, (float)($y + 165), 18, 'rgba(255,255,255,0.6)', 'center', 400);
        } else {
            // BOWLING STATS
            $wickets = Util::safeInt($stats['wickets'] ?? 0, 0);
            $runs = Util::safeInt($stats['runs'] ?? 0, 0);
            $statLine = (string)$wickets . '/' . (string)$runs;
            self::text($im, $statLine, (float)$centerX, (float)($y + 125), 32, '#22d3ee', 'center', 800);
            
            $balls = Util::safeInt($stats['balls'] ?? 0, 0);
            $overs = (string)((int)floor($balls / 6)) . '.' . (string)($balls % 6);
            $economy = $balls > 0 ? number_format(($runs / $balls) * 6, 2) : '0.00';
            self::text($im, $overs . ' OV  ' . $economy . ' ECON', (float)$centerX, (float)($y + 165), 18, 'rgba(255,255,255,0.6)', 'center', 400);
        }
    }

    /**
     * Draw text on image
     *
     * @param Imagick $im Image object
     * @param string $text Text to draw
     * @param float $x X position
     * @param float $y Y position
     * @param int $size Font size
     * @param string $color Text color
     * @param string $align Text alignment
     * @param int $weight Font weight
     * @return void
     * @throws ImagickException
     */
    private static function text(Imagick $im, string $text, float $x, float $y, int $size, string $color, string $align = 'left', int $weight = 400): void
    {
        $d = new ImagickDraw();
        $d->setFillColor(new ImagickPixel($color));
        
        $fontPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . 'Inter_24pt-Bold.ttf';
        if (file_exists($fontPath)) {
            $d->setFont($fontPath);
        }
        
        $d->setFontSize($size);
        $d->setFontWeight($weight);
        
        if ($align === 'center') {
            $d->setTextAlignment(Imagick::ALIGN_CENTER);
        } elseif ($align === 'right') {
            $d->setTextAlignment(Imagick::ALIGN_RIGHT);
        }
        
        $im->annotateImage($d, $x, $y, 0, $text);
    }

    /**
     * Get summary for team innings
     *
     * @param int $teamIndex Team index (0 or 1)
     * @param array<int, mixed> $innings Innings data
     * @param int $bpo Balls per over
     * @return array{0: int, 1: int, 2: string} Runs, wickets, overs
     */
    private static function summaryFor(int $teamIndex, array $innings, int $bpo): array
    {
        foreach ($innings as $inn) {
            if (!is_array($inn)) {
                continue;
            }
            
            $battingTeam = null;
            if (isset($inn['batting'])) {
                $battingTeam = $inn['batting'];
            }
            
            if ($battingTeam === $teamIndex) {
                $r = Util::safeInt($inn['runs'] ?? 0, 0);
                $w = Util::safeInt($inn['wickets'] ?? 0, 0);
                $bs = Util::safeInt($inn['balls'] ?? 0, 0);
                $ballsPerOver = max(1, $bpo);
                $overs = (string)((int)floor($bs / $ballsPerOver)) . '.' . (string)($bs % $ballsPerOver);
                return [$r, $w, $overs];
            }
        }
        
        return [0, 0, '0.0'];
    }

    /**
     * Get top batter from ALL innings
     *
     * @param array<int, mixed> $innings All innings data
     * @return array<string, mixed> Top batter stats
     */
    private static function topBatterFromAllInnings(array $innings): array
    {
        $top = ['name' => '—', 'runs' => 0, 'balls' => 0, 'fours' => 0, 'sixes' => 0];
        
        foreach ($innings as $inn) {
            if (!is_array($inn)) {
                continue;
            }
            
            $rows = [];
            if (isset($inn['batStats']) && is_array($inn['batStats'])) {
                $rows = $inn['batStats'];
            }
            
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                
                $rowRuns = Util::safeInt($row['runs'] ?? 0, 0);
                
                if ($rowRuns > $top['runs']) {
                    $top = [
                        'name' => Util::safeStr($row['name'] ?? '—', '—'),
                        'runs' => $rowRuns,
                        'balls' => Util::safeInt($row['balls'] ?? 0, 0),
                        'fours' => Util::safeInt($row['fours'] ?? 0, 0),
                        'sixes' => Util::safeInt($row['sixes'] ?? 0, 0),
                    ];
                }
            }
        }
        
        return $top;
    }

    /**
     * Get top bowler from ALL innings
     *
     * @param array<int, mixed> $innings All innings data
     * @return array<string, mixed> Top bowler stats
     */
    private static function topBowlerFromAllInnings(array $innings): array
    {
        $top = ['name' => '—', 'wickets' => 0, 'runs' => 999999, 'balls' => 0];
        
        foreach ($innings as $inn) {
            if (!is_array($inn)) {
                continue;
            }
            
            $rows = [];
            if (isset($inn['bowlStats']) && is_array($inn['bowlStats'])) {
                $rows = $inn['bowlStats'];
            }
            
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                
                $w = Util::safeInt($row['wickets'] ?? 0, 0);
                $r = Util::safeInt($row['runs'] ?? 0, 0);
                
                // Better bowler = more wickets OR same wickets but fewer runs
                if ($w > $top['wickets'] || ($w === $top['wickets'] && $w > 0 && $r < $top['runs'])) {
                    $top = [
                        'name' => Util::safeStr($row['name'] ?? '—', '—'),
                        'wickets' => $w,
                        'runs' => $r,
                        'balls' => Util::safeInt($row['balls'] ?? 0, 0),
                    ];
                }
            }
        }
        
        return $top;
    }

    /**
     * Get top batter from innings (DEPRECATED - use topBatterFromAllInnings)
     *
     * @param array<string, mixed> $inn Innings data
     * @return array<string, mixed> Top batter stats
     */
    private static function topBatter(array $inn): array
    {
        $top = ['name' => '—', 'runs' => 0, 'balls' => 0, 'fours' => 0, 'sixes' => 0];
        
        $rows = [];
        if (isset($inn['batStats']) && is_array($inn['batStats'])) {
            $rows = $inn['batStats'];
        }
        
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            
            $rowRuns = Util::safeInt($row['runs'] ?? 0, 0);
            
            if ($rowRuns > $top['runs']) {
                $top = [
                    'name' => Util::safeStr($row['name'] ?? '—', '—'),
                    'runs' => $rowRuns,
                    'balls' => Util::safeInt($row['balls'] ?? 0, 0),
                    'fours' => Util::safeInt($row['fours'] ?? 0, 0),
                    'sixes' => Util::safeInt($row['sixes'] ?? 0, 0),
                ];
            }
        }
        
        return $top;
    }

    /**
     * Get top bowler from innings (DEPRECATED - use topBowlerFromAllInnings)
     *
     * @param array<string, mixed> $inn Innings data
     * @return array<string, mixed> Top bowler stats
     */
    private static function topBowler(array $inn): array
    {
        $top = ['name' => '—', 'wickets' => 0, 'runs' => 0, 'balls' => 0];
        
        $rows = [];
        if (isset($inn['bowlStats']) && is_array($inn['bowlStats'])) {
            $rows = $inn['bowlStats'];
        }
        
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            
            $w = Util::safeInt($row['wickets'] ?? 0, 0);
            
            if ($w > $top['wickets']) {
                $top = [
                    'name' => Util::safeStr($row['name'] ?? '—', '—'),
                    'wickets' => $w,
                    'runs' => Util::safeInt($row['runs'] ?? 0, 0),
                    'balls' => Util::safeInt($row['balls'] ?? 0, 0),
                ];
            }
        }
        
        return $top;
    }

    /**
     * Determine match winner
     *
     * @param int $aRuns Team A runs
     * @param int $bRuns Team B runs
     * @param int $aWkts Team A wickets
     * @param int $bWkts Team B wickets
     * @param string $teamA Team A name
     * @param string $teamB Team B name
     * @return string Winner message
     */
    private static function determineWinner(int $aRuns, int $bRuns, int $aWkts, int $bWkts, string $teamA, string $teamB): string
    {
        if ($aRuns === $bRuns) {
            return 'Match Tied';
        }
        
        if ($aRuns > $bRuns) {
            $margin = $aRuns - $bRuns;
            return $teamA . ' won by ' . (string)$margin . ' runs';
        }
        
        $wicketsLeft = 10 - $bWkts;
        return $teamB . ' won by ' . (string)$wicketsLeft . ' wickets';
    }

    /**
     * Save image to file
     *
     * @param Imagick $im Image object
     * @param string $path File path
     * @return void
     * @throws ImagickException
     */
    private static function save(Imagick $im, string $path): void
    {
        $im->writeImage($path);
        $im->clear();
        $im->destroy();
    }
}