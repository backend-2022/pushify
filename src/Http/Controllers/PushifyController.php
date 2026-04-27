<?php

namespace Badawy\Pushify\Http\Controllers;

use Badawy\Pushify\Contracts\PushifyServiceInterface;
use Badawy\Pushify\Http\Requests\StorePushifyRequest;
use Badawy\Pushify\Http\Resources\PushifyResource;
use Badawy\Pushify\Models\Pushify;
use Illuminate\Routing\Controller;

class PushifyController extends Controller
{
    public function index()
    {
        return PushifyResource::collection(
            Pushify::query()->latest()->paginate()
        );
    }

    public function store(StorePushifyRequest $request, PushifyServiceInterface $push)
    {
        $notification = $push->sendToAll(
            title: $request->string('title')->toString(),
            body: $request->string('body')->toString(),
            data: $request->input('data', []),
            image: $request->input('image'),
            scheduledAt: $request->input('scheduled_at'),
        );

        return PushifyResource::make($notification)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Pushify $pushify)
    {
        return PushifyResource::make($pushify);
    }

    public function send(Pushify $pushify, PushifyServiceInterface $push)
    {
        return PushifyResource::make($push->send($pushify));
    }
}
