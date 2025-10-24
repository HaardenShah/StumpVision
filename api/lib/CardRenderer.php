<?php
declare(strict_types=1);

namespace StumpVision;

use Imagick;
use ImagickDraw;
use ImagickPixel;
use ImagickException;
use RuntimeException;

require_once __DIR__ . '/Util.php';

final class CardRenderer
{
    /** Build premium gradient share card with modern design */
    public static function render(array $match, string $cardsDir, string $baseName): array
    {
        if (!extension_loaded('imagick')) {
            throw new RuntimeException('Imagick extension not available');
        }

        // Extract data safely
        $meta = (isset($match['meta']) && is_array($match['meta'])) ? $match['meta'] : [];
        $teams = (isset($match['teams']) && is_array($match['teams'])) ? $match['teams'] : [[], []];
        $inns = (isset($match['innings']) && is_array($match['innings'])) ? $match['innings'] : [];

        $teamAName = Util::safeStr($teams[0]['name'] ?? 'Team A', 'Team A');
        $teamBName = Util::safeStr($teams[1]['name'] ?? 'Team B', 'Team B');

        // Get scores
        [$aRuns, $aWkts, $aOvers] = self::summaryFor(0, $inns, 6);
        [$bRuns, $bWkts, $bOvers] = self::summaryFor(1, $inns, 6);

        // Determine winner
        $winner = self::determineWinner($aRuns, $bRuns, $aWkts, $bWkts, $teamAName, $teamBName);

        // Get top performers
        $topBat = self::topBatter($inns[0] ?? []);
        $topBowl = self::topBowler($inns[0] ?? []);

        $coverPng = $cardsDir . "/{$baseName}-card.png";

        try {
            // Create 1080x1920 card (Instagram Story size)
            $card = self::createGradientCanvas();
            
            // Add glassmorphism card overlay
            self::addGlassCard($card, 60, 180, 960, 1560);
            
            // Header - StumpVision branding
            self::text($card, 'STUMPVISION', 540, 120, 32, '#ffffff', 'center', 800);
            self::text($card, 'MATCH SCORECARD', 540, 160, 20, 'rgba(255,255,255,0.7)', 'center', 400);
            
            // Team A Score - Large and prominent
            self::addTeamSection($card, $teamAName, $aRuns, $aWkts, $aOvers, 260, true);
            
            // VS divider with match info
            self::text($card, 'VS', 540, 520, 28, 'rgba(255,255,255,0.5)', 'center', 600);
            $matchInfo = Util::safeInt($meta['oversPerSide'] ?? 20) . ' OVERS';
            self::text($card, $matchInfo, 540, 560, 16, 'rgba(255,255,255,0.4)', 'center', 400);
            
            // Team B Score
            self::addTeamSection($card, $teamBName, $bRuns, $bWkts, $bOvers, 640, false);
            
            // Winner banner with gradient
            self::addWinnerBanner($card, $winner, 1040);
            
            // Top Performers section
            self::addPerformersSection($card, $topBat, $topBowl, 1180);
            
            // Footer - Clean and minimal
            self::text($card, 'Powered by StumpVision', 540, 1840, 18, 'rgba(255,255,255,0.4)', 'center', 400);
            
            // Save the card
            self::save($card, $coverPng);
            
            return [[$coverPng], $coverPng];
        } catch (ImagickException $e) {
            throw new RuntimeException('Card render error: ' . $e->getMessage());
        }
    }

    /**
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
     * @throws ImagickException
     */
    private static function addGlassCard(Imagick $im, int $x, int $y, int $w, int $h): void
    {
        // Create frosted glass effect with rounded corners
        $glass = new ImagickDraw();
        $glass->setFillColor(new ImagickPixel('rgba(255, 255, 255, 0.1)'));
        $glass->setStrokeColor(new ImagickPixel('rgba(255, 255, 255, 0.2)'));
        $glass->setStrokeWidth(2);
        $glass->roundRectangle($x, $y, $x + $w, $y + $h, 40, 40);
        $im->drawImage($glass);
    }

    /**
     * @throws ImagickException
     */
    private static function addTeamSection(Imagick $im, string $team, int $runs, int $wkts, string $overs, int $y, bool $isFirst): void
    {
        // Team name with subtle uppercase
        self::text($im, strtoupper($team), 540, $y, 24, 'rgba(255,255,255,0.8)', 'center', 600);
        
        // Massive score display
        $scoreY = $y + 60;
        self::text($im, (string)$runs, 460, $scoreY, 96, '#ffffff', 'right', 800);
        self::text($im, '/', 540, $scoreY, 80, 'rgba(255,255,255,0.5)', 'center', 400);
        self::text($im, (string)$wkts, 620, $scoreY, 96, '#ffffff', 'left', 800);
        
        // Overs info below score
        self::text($im, $overs . ' OVERS', 540, $scoreY + 80, 20, 'rgba(255,255,255,0.6)', 'center', 400);
        
        // Add subtle divider line
        $divider = new ImagickDraw();
        $divider->setStrokeColor(new ImagickPixel('rgba(255,255,255,0.2)'));
        $divider->setStrokeWidth(2);
        $divider->line(240, $y + 160, 840, $y + 160);
        $im->drawImage($divider);
    }

    /**
     * @throws ImagickException
     */
    private static function addWinnerBanner(Imagick $im, string $winner, int $y): void
    {
        // Highlight box with gradient
        $box = new ImagickDraw();
        $box->setFillColor(new ImagickPixel('rgba(34, 211, 238, 0.2)'));
        $box->setStrokeColor(new ImagickPixel('rgba(34, 211, 238, 0.4)'));
        $box->setStrokeWidth(2);
        $box->roundRectangle(120, $y, 960, $y + 80, 20, 20);
        $im->drawImage($box);
        
        // Winner text
        self::text($im, '🏆 ' . $winner, 540, $y + 55, 28, '#22d3ee', 'center', 700);
    }

    /**
     * @throws ImagickException
     */
    private static function addPerformersSection(Imagick $im, array $topBat, array $topBowl, int $y): void
    {
        // Section header
        self::text($im, 'TOP PERFORMERS', 540, $y, 18, 'rgba(255,255,255,0.5)', 'center', 600);
        
        // Top Batter
        $batY = $y + 60;
        self::addPerformerCard($im, 180, $batY, 380, 180, '🏏 BATTING', $topBat);
        
        // Top Bowler
        self::addPerformerCard($im, 600, $batY, 380, 180, '⚡ BOWLING', $topBowl);
    }

    /**
     * @throws ImagickException
     */
    private static function addPerformerCard(Imagick $im, int $x, int $y, int $w, int $h, string $label, array $stats): void
    {
        // Card background
        $card = new ImagickDraw();
        $card->setFillColor(new ImagickPixel('rgba(255, 255, 255, 0.08)'));
        $card->setStrokeColor(new ImagickPixel('rgba(255, 255, 255, 0.15)'));
        $card->setStrokeWidth(1);
        $card->roundRectangle($x, $y, $x + $w, $y + $h, 16, 16);
        $im->drawImage($card);
        
        // Label
        self::text($im, $label, $x + $w/2, $y + 35, 14, 'rgba(255,255,255,0.5)', 'center', 600);
        
        // Player name
        $name = Util::safeStr($stats['name'] ?? '—', '—');
        self::text($im, $name, $x + $w/2, $y + 75, 22, '#ffffff', 'center', 700);
        
        // Stats
        if (isset($stats['runs'])) {
            // Batting stats
            $statLine = Util::safeInt($stats['runs']) . ' (' . Util::safeInt($stats['balls']) . ')';
            self::text($im, $statLine, $x + $w/2, $y + 115, 28, '#22d3ee', 'center', 800);
            
            // Boundaries
            $boundaries = Util::safeInt($stats['fours'] ?? 0) . '×4  ' . Util::safeInt($stats['sixes'] ?? 0) . '×6';
            self::text($im, $boundaries, $x + $w/2, $y + 145, 16, 'rgba(255,255,255,0.6)', 'center', 400);
        } else {
            // Bowling stats
            $statLine = Util::safeInt($stats['wickets'] ?? 0) . '/' . Util::safeInt($stats['runs'] ?? 0);
            self::text($im, $statLine, $x + $w/2, $y + 115, 28, '#22d3ee', 'center', 800);
            
            // Overs
            $balls = Util::safeInt($stats['balls'] ?? 0);
            $overs = floor($balls / 6) . '.' . ($balls % 6);
            self::text($im, $overs . ' OVERS', $x + $w/2, $y + 145, 16, 'rgba(255,255,255,0.6)', 'center', 400);
        }
    }

    /**
     * @throws ImagickException
     */
    private static function text(Imagick $im, string $text, float $x, float $y, int $size, string $color, string $align = 'left', int $weight = 400): void
    {
        $d = new ImagickDraw();
        $d->setFillColor(new ImagickPixel($color));
        
        // Try to use Inter font if available, fallback to system font
        $fontPath = __DIR__ . '/../../assets/fonts/Inter_24pt-Bold.ttf';
        if (file_exists($fontPath)) {
            $d->setFont($fontPath);
        }
        // If font doesn't exist, ImageMagick will use system default
        
        $d->setFontSize($size);
        $d->setFontWeight($weight);
        
        if ($align === 'center') {
            $d->setTextAlignment(Imagick::ALIGN_CENTER);
        } elseif ($align === 'right') {
            $d->setTextAlignment(Imagick::ALIGN_RIGHT);
        }
        
        $im->annotateImage($d, $x, $y, 0, $text);
    }

    private static function summaryFor(int $teamIndex, array $innings, int $bpo): array
    {
        foreach ($innings as $inn) {
            if (!is_array($inn)) {
                continue;
            }
            if (($inn['batting'] ?? null) === $teamIndex) {
                $r = Util::safeInt($inn['runs'] ?? 0);
                $w = Util::safeInt($inn['wickets'] ?? 0);
                $bs = Util::safeInt($inn['balls'] ?? 0);
                $overs = floor($bs / max(1, $bpo)) . "." . ($bs % max(1, $bpo));
                return [$r, $w, $overs];
            }
        }
        return [0, 0, '0.0'];
    }

    private static function topBatter(array $inn): array
    {
        $top = ['name' => '—', 'runs' => 0, 'balls' => 0, 'fours' => 0, 'sixes' => 0];
        $rows = isset($inn['batStats']) && is_array($inn['batStats']) ? $inn['batStats'] : [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            if (Util::safeInt($row['runs'] ?? 0) > $top['runs']) {
                $top = [
                    'name' => Util::safeStr($row['name'] ?? '—', '—'),
                    'runs' => Util::safeInt($row['runs'] ?? 0),
                    'balls' => Util::safeInt($row['balls'] ?? 0),
                    'fours' => Util::safeInt($row['fours'] ?? 0),
                    'sixes' => Util::safeInt($row['sixes'] ?? 0),
                ];
            }
        }
        return $top;
    }

    private static function topBowler(array $inn): array
    {
        $top = ['name' => '—', 'wickets' => 0, 'runs' => 0, 'balls' => 0];
        $rows = isset($inn['bowlStats']) && is_array($inn['bowlStats']) ? $inn['bowlStats'] : [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $w = Util::safeInt($row['wickets'] ?? 0);
            if ($w > $top['wickets']) {
                $top = [
                    'name' => Util::safeStr($row['name'] ?? '—', '—'),
                    'wickets' => $w,
                    'runs' => Util::safeInt($row['runs'] ?? 0),
                    'balls' => Util::safeInt($row['balls'] ?? 0),
                ];
            }
        }
        return $top;
    }

    private static function determineWinner(int $aRuns, int $bRuns, int $aWkts, int $bWkts, string $teamA, string $teamB): string
    {
        if ($aRuns === $bRuns) {
            return 'Match Tied';
        }
        if ($aRuns > $bRuns) {
            $margin = $aRuns - $bRuns;
            return "$teamA won by $margin runs";
        } else {
            $wicketsLeft = 10 - $bWkts;
            return "$teamB won by $wicketsLeft wickets";
        }
    }

    /**
     * @throws ImagickException
     */
    private static function save(Imagick $im, string $path): void
    {
        $im->writeImage($path);
        $im->clear();
        $im->destroy();
    }
}