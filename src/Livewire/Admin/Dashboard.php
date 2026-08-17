<?php

namespace Pilot\Core\Livewire\Admin;

use App\Models\User;
use Livewire\Component;
use Pilot\Core\Models\Activity;
use Pilot\Core\Models\Asset;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\Space;

class Dashboard extends Component
{
    public function render()
    {
        $space = Space::first();

        return view('livewire.admin.dashboard', [
            'space' => $space,
            'pagesCount' => Content::where('type', 'page')->count(),
            'assetsCount' => Asset::count(),
            'usersCount' => User::count(),
            'draftsCount' => Content::where('status', 'draft')->count(),
            'reviewsCount' => Content::where('workflow_status', 'in_review')->count(),
            'changesRequestedCount' => Content::where('workflow_status', 'changes_requested')->count(),
            'scheduledCount' => Content::where('workflow_status', 'scheduled')->count(),
            'expiredAssetsCount' => Asset::whereNotNull('expires_at')->where('expires_at', '<', now())->count(),
            'recentActivities' => Activity::with(['user', 'subject'])->latest()->take(20)->get(),
            'recentPages' => Content::where('type', 'page')
                ->where('updated_by', auth()->id())
                ->latest('updated_at')
                ->take(6)
                ->get(),
        ])->layout('layouts.admin');
    }
}
