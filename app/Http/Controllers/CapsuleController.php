<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCapsuleRequest;
use App\Models\Capsule;
use App\Services\CapsuleStorage;
use App\Services\Mailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CapsuleController extends Controller
{
    public function __construct(
        private CapsuleStorage $storage,
        private Mailer $mailer,
    ) {}

    /** GET / — the current user's capsules, the soonest to open first. */
    public function index(): View
    {
        $capsules = Capsule::where('user_id', Auth::id())
            ->orderBy('open_at')
            ->orderBy('id')
            ->get();

        return view('capsules.index', ['capsules' => $capsules]);
    }

    /** GET /capsules/new */
    public function create(): View
    {
        return view('capsules.create', ['values' => []]);
    }

    /** POST /capsules — validation happens in StoreCapsuleRequest before we get here. */
    public function store(StoreCapsuleRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $data = $request->validated();

        $capsule = new Capsule([
            'user_id' => $user->id,
            'title' => $data['title'],
            'message' => $data['message'],
            'open_at' => $request->openAt(), // UTC, see StoreCapsuleRequest::openAt()
            'is_public' => $request->boolean('is_public'),
            'recipient_email' => $data['recipient_email'] ?? null,
        ]);

        $file = $request->file('attachment');
        if ($file !== null) {
            $capsule->file_path = $this->storage->save($file);
            $capsule->file_name = mb_substr($file->getClientOriginalName(), 0, 255);
            $capsule->file_mime = $file->getMimeType();
        }

        $capsule->save();

        $openAt = $capsule->open_at->format('j M Y, H:i').' UTC';

        // "Send" the confirmation email. This makes the request wait MAIL_DELAY_SECONDS.
        $this->mailer->send(
            $capsule,
            $capsule->recipient_email ?: $user->email,
            "Capsule \"{$capsule->title}\" was sealed for you until {$openAt}."
        );

        return redirect("/capsules/{$capsule->id}")->with('success', 'Capsule sealed.');
    }

    /** GET /capsules/{id} — sealed or opened view. Viewing never opens a capsule. */
    public function show(Capsule $capsule): View
    {
        $user = Auth::user();
        abort_unless($capsule->isVisibleTo($user), 404);

        return view('capsules.show', [
            'capsule' => $capsule,
            'isOwner' => $capsule->isOwnedBy($user),
        ]);
    }

    /** GET /capsules/{id}/file — the attachment, streamed through the app. */
    public function file(Request $request, Capsule $capsule): StreamedResponse
    {
        abort_unless($capsule->isVisibleTo(Auth::user()) && $capsule->hasFile(), 404);
        // Only the owner can get here with a sealed capsule: the content stays secret until it opens.
        abort_unless($capsule->isOpened(), 403, 'This capsule is still sealed.');

        return $this->storage->stream(
            $capsule->file_path,
            $capsule->file_name,
            $capsule->file_mime,
            $request->boolean('download'),
        );
    }

    /** POST /capsules/{id}/delete — owner only; removes the file too. */
    public function destroy(Capsule $capsule): RedirectResponse
    {
        abort_unless($capsule->isOwnedBy(Auth::user()), 404);

        if ($capsule->hasFile()) {
            $this->storage->delete($capsule->file_path);
        }
        $capsule->delete(); // its notifications are deleted by the database (ON DELETE CASCADE)

        return redirect('/')->with('success', 'Capsule deleted.');
    }
}
