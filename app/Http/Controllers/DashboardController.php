<?php

namespace App\Http\Controllers;

use App\Enums\CatalogStatus;
use App\Models\PlantSpecies;
use App\Models\PlantVariety;
use App\Models\TeamInvitation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $email = strtolower($user->email);

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        $moderationStats = null;
        if ($user->canModerateCatalog()) {
            $moderationStats = [
                'pendingSpeciesCount' => PlantSpecies::where('catalog_status', CatalogStatus::PendingReview->value)->whereNull('archived_at')->where('origin', 'user')->count(),
                'pendingVarietiesCount' => PlantVariety::where('catalog_status', CatalogStatus::PendingReview->value)->whereNull('archived_at')->where('origin', 'user')->count(),
            ];
        }

        return Inertia::render('dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'moderationStats' => $moderationStats,
        ]);
    }
}
