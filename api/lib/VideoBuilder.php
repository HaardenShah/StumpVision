<?php
declare(strict_types=1);

/**
 * StumpVision — api/lib/VideoBuilder.php
 * Wraps ffmpeg invocation to stitch still slides into an MP4 with crossfades.
 */

namespace StumpVision;

require_once __DIR__ . '/Util.php';

final class VideoBuilder
{
    /**
     * @param array $slides [s0, s1, s2, s3] absolute file paths
     * @return array [ok:bool, mp4Path:?string, error:?string]
     */
    public static function build(array $slides, string $outPath): array
    {
        $ffmpeg = Util::which('ffmpeg');
        if (!$ffmpeg) {
            return ['ok'=>false, 'mp4Path'=>null, 'error'=>'FFmpeg not found'];
        }

        // durations: [1.2, 2.6, 2.0, 1.0], crossfade 0.35
        $cmd = escapeshellarg($ffmpeg) . ' -y '
             . '-loop 1 -t 1.2 -i ' . escapeshellarg($slides[0]) . ' '
             . '-loop 1 -t 2.6 -i ' . escapeshellarg($slides[1]) . ' '
             . '-loop 1 -t 2.0 -i ' . escapeshellarg($slides[2]) . ' '
             . '-loop 1 -t 1.0 -i ' . escapeshellarg($slides[3]) . ' '
             . '-filter_complex '
             . escapeshellarg(
                 '[0:v]format=yuv420p,setsar=1[v0];' .
                 '[1:v]format=yuv420p,setsar=1[v1];' .
                 '[2:v]format=yuv420p,setsar=1[v2];' .
                 '[3:v]format=yuv420p,setsar=1[v3];' .
                 '[v0][v1]xfade=transition=fade:duration=0.35:offset=0.85[v01];' .
                 '[v01][v2]xfade=transition=fade:duration=0.35:offset=2.40[v02];' .
                 '[v02][v3]xfade=transition=fade:duration=0.35:offset=4.05[vout]'
             ) . ' '
             . '-map "[vout]" -r 30 -c:v libx264 -pix_fmt yuv420p -movflags +faststart '
             . escapeshellarg($outPath) . ' 2>&1';

        exec($cmd, $out, $ret);
        if ($ret === 0 && is_file($outPath)) {
            return ['ok'=>true, 'mp4Path'=>$outPath, 'error'=>null];
        }
        return ['ok'=>false, 'mp4Path'=>null, 'error'=>'FFmpeg run failed'];
    }
}
