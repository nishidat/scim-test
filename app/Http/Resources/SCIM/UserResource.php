<?php

namespace App\Http\Resources\SCIM;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * User resource constructor.
     *
     * @param mixed $resource
     *
     * @return void
     */
    public function __construct($resource)
    {
        static::withoutWrapping();
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'],
            'id' => $this->email,
            'externalId' => $this->email,
            'userName' => $this->email,
            'name' => [
                'givenName' => $this->first_name,
                'familyName' => $this->last_name,
            ],
            'active' => $this->active,
            'meta' => [
                'resourceType' => 'User',
                'created' => $this->created_at->toIso8601String(),
                'lastModified' => $this->updated_at->toIso8601String(),
                'location' => route('api.user.get', [$this->email]),
            ],
        ];
    }
}