<?php


namespace App\Http\Controllers;
use App\Image;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use App\Http\Requests\ImageRequest;
use SIAM\AdminSiamAccessToken;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ImageController
{
    /**
     * Holds the unique end point for store images remotely.
     *
     * @var string
     */
    const STORE_IMAGE_REMOTE_URL = 'https://test.rxflodev.com';

    public function store(ImageRequest $request)
    {
        $image = base64_encode(file_get_contents($request->file('file')->path()));

        $httpClient = new HttpClient([
            'base_uri' => self::STORE_IMAGE_REMOTE_URL
        ]);

        try {
            $res = $httpClient->post('', [
                'headers' => [],
                'multipart' => [
                    [
                        'contents' => $image,
                        'name' => 'imageData',
                    ],
                ],
            ]);
        } catch (ClientException $exception) {
            return response()->json(json_decode($exception->getMessage(), true), $exception->getCode());
        } catch (GuzzleException $exception) {
            return response()->json(json_decode($exception->getMessage(), true), $exception->getCode());
        }

        $content =  json_decode($res->getBody()->getContents(), true);

        if ($res->getStatusCode() == 200) {
            $image = Image::create([
                'name' => $request->file('file')->getClientOriginalName(),
                'url' => $content['url']
            ]);
        }

        return response()->json(['response' => $content, 'id' => $image->id]);
    }

    /**
     * Retrieves all images of the database.
     *
     */
    public function index()
    {
        return Image::orderBy('created_at', 'desc')->get();
    }

    /**
     * Deletes one image from the database.
     *
     * @return \Illuminate\Http\JsonResponse
     * @var int
     */
    public function destroy($image)
    {
        if (Image::find($image)->delete()) {
            return response()->json(['response' => 'Image deleted successfully']);
        }

        throw new NotFoundHttpException('The image does not exists in the database');
    }

}
