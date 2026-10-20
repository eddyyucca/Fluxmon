<?php

namespace App\Services\Config;

/**
 * Line diff for configuration text. Trims the shared head and tail, then runs a
 * longest-common-subsequence over what is left (falling back to a set diff for huge changes).
 */
class DiffService
{
    private const LCS_LIMIT = 4_000_000; // cells; ~2000 x 2000 changed lines

    /**
     * @return list<array{type: string, text: string}> type is ' ' (kept), '+' (added) or '-' (removed)
     */
    public function lines(string $old, string $new): array
    {
        $a = $old === '' ? [] : preg_split('/\R/', rtrim($old));
        $b = $new === '' ? [] : preg_split('/\R/', rtrim($new));

        $head = 0;
        $max = min(count($a), count($b));
        while ($head < $max && $a[$head] === $b[$head]) {
            $head++;
        }
        $tail = 0;
        while ($tail < $max - $head && $a[count($a) - 1 - $tail] === $b[count($b) - 1 - $tail]) {
            $tail++;
        }

        $midA = array_slice($a, $head, count($a) - $head - $tail);
        $midB = array_slice($b, $head, count($b) - $head - $tail);

        $out = array_map(fn ($t) => ['type' => ' ', 'text' => $t], array_slice($a, 0, $head));
        array_push($out, ...$this->middle($midA, $midB));
        array_push($out, ...array_map(fn ($t) => ['type' => ' ', 'text' => $t], array_slice($a, count($a) - $tail)));

        return $out;
    }

    /** Only the changed lines, each with `$context` unchanged neighbours, and "…" gaps between hunks. */
    public function changes(string $old, string $new, int $context = 2): array
    {
        $all = $this->lines($old, $new);
        $keep = [];
        foreach ($all as $i => $line) {
            if ($line['type'] !== ' ') {
                for ($j = max(0, $i - $context); $j <= min(count($all) - 1, $i + $context); $j++) {
                    $keep[$j] = true;
                }
            }
        }

        $out = [];
        $last = -1;
        foreach (array_keys($keep) as $i) {
            if ($last !== -1 && $i > $last + 1) {
                $out[] = ['type' => '…', 'text' => ''];
            }
            $out[] = $all[$i];
            $last = $i;
        }

        return $out;
    }

    private function middle(array $a, array $b): array
    {
        if (! $a || ! $b) {
            return [...array_map(fn ($t) => ['type' => '-', 'text' => $t], $a), ...array_map(fn ($t) => ['type' => '+', 'text' => $t], $b)];
        }

        if (count($a) * count($b) > self::LCS_LIMIT) {
            $inB = array_flip($b);
            $inA = array_flip($a);

            return [
                ...array_map(fn ($t) => ['type' => isset($inB[$t]) ? ' ' : '-', 'text' => $t], $a),
                ...array_map(fn ($t) => ['type' => '+', 'text' => $t], array_values(array_filter($b, fn ($t) => ! isset($inA[$t])))),
            ];
        }

        $n = count($a);
        $m = count($b);
        $lcs = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $lcs[$i][$j] = $a[$i] === $b[$j] ? $lcs[$i + 1][$j + 1] + 1 : max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
            }
        }

        $out = [];
        $i = $j = 0;
        while ($i < $n && $j < $m) {
            if ($a[$i] === $b[$j]) {
                $out[] = ['type' => ' ', 'text' => $a[$i]];
                $i++;
                $j++;
            } elseif ($lcs[$i + 1][$j] >= $lcs[$i][$j + 1]) {
                $out[] = ['type' => '-', 'text' => $a[$i++]];
            } else {
                $out[] = ['type' => '+', 'text' => $b[$j++]];
            }
        }
        for (; $i < $n; $i++) {
            $out[] = ['type' => '-', 'text' => $a[$i]];
        }
        for (; $j < $m; $j++) {
            $out[] = ['type' => '+', 'text' => $b[$j]];
        }

        return $out;
    }
}
