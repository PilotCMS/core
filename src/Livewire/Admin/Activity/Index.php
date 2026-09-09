<?php

namespace Pilot\Core\Livewire\Admin\Activity;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Pilot\Core\Models\Activity;
use Pilot\Core\Models\Block;
use Pilot\Core\Models\Content;

class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(as: 'action', except: 'all')]
    public string $actionFilter = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedActionFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);

        $activities = Activity::query()
            ->with([
                'space',
                'user',
                'subject' => function (MorphTo $morphTo): void {
                    $morphTo->morphWith([
                        Block::class => ['blockType', 'content'],
                    ]);
                },
            ])
            ->when($this->actionFilter !== 'all', fn ($query) => $query->where('action', $this->actionFilter))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('action', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%'))
                        ->orWhere(function ($contentQuery) use ($search): void {
                            $contentQuery
                                ->where('subject_type', Content::class)
                                ->whereIn('subject_id', Content::query()
                                    ->select('id')
                                    ->where('name', 'like', '%'.$search.'%'));
                        })
                        ->orWhere(function ($blockQuery) use ($search): void {
                            $blockQuery
                                ->where('subject_type', Block::class)
                                ->whereIn('subject_id', Block::query()
                                    ->select('id')
                                    ->whereHas('content', fn ($contentQuery) => $contentQuery->where('name', 'like', '%'.$search.'%')));
                        });
                });
            })
            ->latest()
            ->paginate(30);

        return view('livewire.admin.activity.index', [
            'activities' => $activities,
            'actions' => Activity::query()->distinct()->orderBy('action')->pluck('action'),
        ])->layout('layouts.admin');
    }
}
