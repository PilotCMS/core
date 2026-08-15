<?php

namespace Pilot\Core\Console\Commands;

use Pilot\Core\Models\Content;
use Pilot\Core\Support\Cms\ContentLifecycle;
use Illuminate\Console\Command;

class PublishScheduledContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pilot:publish-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish CMS content entries whose scheduled publish time has arrived';

    /**
     * Execute the console command.
     */
    public function handle(ContentLifecycle $lifecycle): int
    {
        $published = 0;

        Content::query()
            ->where('workflow_status', 'scheduled')
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now())
            ->orderBy('scheduled_for')
            ->each(function (Content $content) use ($lifecycle, &$published): void {
                $lifecycle->publish($content);
                $published++;
            });

        $this->components->info("Published {$published} scheduled content entries.");

        return self::SUCCESS;
    }
}
