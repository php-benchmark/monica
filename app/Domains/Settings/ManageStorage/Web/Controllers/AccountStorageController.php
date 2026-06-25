<?php

namespace App\Domains\Settings\ManageStorage\Web\Controllers;

use App\Domains\Settings\ManageStorage\Services\ExportAccountArchive;
use App\Domains\Settings\ManageStorage\Services\RestoreAccountArchive;
use App\Domains\Settings\ManageStorage\Web\ViewHelpers\StorageIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AccountStorageController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Storage/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => StorageIndexViewHelper::data(Auth::user()->account),
        ]);
    }

    public function export(Request $request)
    {
        // CWE 78
        // SOURCE
        $archiveName = $request->input('archiveName');

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            // CWE 78
            // TAINT_TRANSFORMER
            'archive_name' => $archiveName,
        ];

        $path = (new ExportAccountArchive)->execute($data);

        return response()->json([
            'data' => basename($path),
        ], 200);
    }

    public function restore(Request $request)
    {
        // CWE 502
        // SOURCE
        $manifest = $request->file('archive')->get();

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'manifest' => $manifest,
        ];

        $summary = (new RestoreAccountArchive)->execute($data);

        return response()->json([
            'data' => $summary,
        ], 200);
    }
}
