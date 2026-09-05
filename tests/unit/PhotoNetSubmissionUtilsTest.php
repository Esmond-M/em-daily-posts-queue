<?php

use EmDailyPostsQueue\init_plugin\Classes\PhotoNetSubmissionUtils;
use PHPUnit\Framework\TestCase;

final class PhotoNetSubmissionUtilsTest extends TestCase
{
    /** @dataProvider storedQueues */
    public function testDecodesStoredQueueWithoutChangingItemOrder(string $stored, array $expected): void
    {
        // Characterize the existing private decoder without changing the production API.
        $utils = new PhotoNetSubmissionUtils();
        $decoder = new ReflectionMethod($utils, 'decode_queue');
        $decoder->setAccessible(true);
        self::assertSame($expected, $decoder->invoke($utils, $stored));
    }

    public static function storedQueues(): array
    {
        $queue = [['postid' => 5, 'queueNumber' => 1], ['postid' => 2, 'queueNumber' => 2]];
        return [
            'empty storage' => ['', []],
            'empty JSON queue' => ['[]', []],
            'JSON preserves order' => [json_encode($queue), $queue],
            'legacy storage preserves order' => [base64_encode(serialize($queue)), $queue],
            'install greeting' => [json_encode(['message' => 'Congratulations']), []],
            'invalid storage' => ['not-json-not-base64-garbage!!!', []],
            'missing post ID' => [json_encode([['queueNumber' => 1], $queue[1]]), [$queue[1]]],
            'missing position' => [json_encode([['postid' => 5], $queue[1]]), [$queue[1]]],
            'scalar entries' => [json_encode(['message', null, 7, $queue[0]]), [$queue[0]]],
        ];
    }

    /** @dataProvider queueComparisons */
    public function testComparesQueueSnapshots(array $current, array $snapshot, array $expected): void
    {
        self::assertSame($expected, (new PhotoNetSubmissionUtils())->edpqcompareMultiDimensional($current, $snapshot));
    }

    public static function queueComparisons(): array
    {
        $first = ['postid' => 5, 'queueNumber' => 1];
        $second = ['postid' => 2, 'queueNumber' => 2];
        return [
            'identical queues' => [[$first, $second], [$first, $second], []],
            'empty queues' => [[], [], []],
            'new submission' => [[$first, $second], [$first], [1 => $second]],
            'changed post' => [[['postid' => 8, 'queueNumber' => 1]], [$first], [0 => ['postid' => 8]]],
            'changed position' => [[['postid' => 5, 'queueNumber' => 2]], [$first], [0 => ['queueNumber' => 2]]],
            'strict types' => [[['postid' => '5', 'queueNumber' => 1]], [$first], [0 => ['postid' => '5']]],
        ];
    }

    public function testRejectsNonArrayCurrentQueue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new PhotoNetSubmissionUtils())->edpqcompareMultiDimensional(null, []);
    }
}
