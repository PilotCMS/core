<?php

namespace Pilot\Core\Console\Commands;

use Illuminate\Console\Command;
use Pilot\Core\Models\Content;
use Pilot\Core\Support\Cms\ContentLifecycle;

class BackfillPublishedContentRevisions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pilot:backfill-published-revisions {--dry-run : Count eligible content without creating revisions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create published revision records for already-published content missing published_revision_id';

    /**
     * Execute the console command.
     */
    public function handle(ContentLifecycle $lifecycle): int
    {
        $query = Content::query()
            ->where('status', 'published')
            ->whereNull('published_revision_id');

        if ($this->option('dry-run')) {
            $this->components->info($query->count().' published content entries need a published revision.');

            return self::SUCCESS;
        }

        $backfilled = 0;

        $query->orderBy('id')->each(function (Content $content) use ($lifecycle, &$backfilled): void {
            $revision = $lifecycle->createRevision(
                $content,
                'Published backfill',
                $content->updated_by,
                'published',
                meta: ['backfilled' => true],
            );

            $content->update(['published_revision_id' => $revision->id]);
            $backfilled++;
        });

        $this->components->info("Backfilled {$backfilled} published content revisions.");

        return self::SUCCESS;
    }
}
