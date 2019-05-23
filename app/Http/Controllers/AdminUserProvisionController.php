<?php

namespace App\Http\Controllers;

use App\Http\Resources\SCIM\UserResource;
//use Illuminate\Foundation\Auth\User;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AdminUserProvisionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @throws \Exception
     */
    public function index(Request $request)
    {
        $filter = $request->get('filter');

        if ($filter && preg_match('/userName eq (.*)/i', $filter, $matches)) {
            $users = User::where('email', $matches[1])->get();
        } else {
            $users = User::all('email');
        }

        $users = $users->map(function ($user) {
            return ['userName' => $user->email];
        });

        $return = [
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'],
            'totalResults' => $users->count(),
        ];

        if ($users->count()) {
            $return['Resources'] = $users;
        }
        return response()->json($return)->setStatusCode(Response::HTTP_OK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws \Exception
     */
    public function create()
    {
        throw new \Exception('Not implemented');
    }

    public function store(Request $request)
    {
        $data = $request->all();

        if (User::where('email', $data['userName'])->count()) {
            return $this->updateUser($request, $data['userName']);
        }

        $user = User::create([
            'first_name' => $data['name']['givenName'],
            'last_name' => $data['name']['familyName'],
            'username' => $data['userName'],
            'email' => $data['userName'],
            'active' => $data['active'],
            'password' => Hash::make('password'),
        ]);

        return UserResource::make($user)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, string $email)
    {
        try {
            $user = User::where($email)->firstOrFail();
        } catch (\Exception $exception) {
            return $this->scimError('User does not exist', Response::HTTP_NOT_FOUND);
        }

        return UserResource::make($user)
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function update(Request $request, string $email)
    {
        return $this->updateUser($request, $email);
    }

    private function updateUser($request, string $email)
    {
        $user = User::where('email', $email)->firstOrFail();

        $validatedData = $request->all();
        $active = Arr::get($validatedData, 'active') ??
            Arr::get($validatedData, 'Operations.value.active') ??
            null;

        // We only care about updating the user's secure access on activation,
        // so return early if there's been no change to their active status
        if ($active === null) {
            return UserResource::make($user)
                ->response()
                ->setStatusCode(Response::HTTP_OK);
        }

        $user->active = $active;

        // If user is active, ensure secure access permission
//        if ($user->active && !$user->hasAccess('secure.access')) {
//            $user->updatePermission('secure.access', true, true);
//        }

        if ($user->isDirty()) {
            $user->save();
        }

        return UserResource::make($user)
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    /**
     * Returns a SCIM-formatted error message
     *
     * @param string|null $message
     * @param int $statusCode
     *
     * @return JsonResponse
     */
    protected function scimError(?string $message, int $statusCode): JsonResponse
    {
        return response()
            ->json([
                'schemas' => ["urn:ietf:params:scim:api:messages:2.0:Error"],
                'detail' => $message ?? 'An error occured',
                'status' => $statusCode,
            ])->setStatusCode($statusCode);
    }
}