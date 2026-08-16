<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\Product;
use App\Services\UploadException;
use App\Services\UploadService;

class OfferController extends BaseAdminController
{
    public function index(): void
    {
        View::render('admin.offers.index', [
            'pageTitle' => 'Offers',
            'activeNav' => 'offers',
            'onOffer' => Product::onOffer(),
            'notOnOffer' => Product::notOnOffer(),
        ]);
    }

    public function update(string $id): void
    {
        if (csrfVerify(Request::post('csrf_token'))) {
            $price = (float) Request::post('price', 0);
            $original = Request::post('original_price', '') !== '' ? (float) Request::post('original_price') : null;
            $product = Product::findRawById((int) $id);
            $offerImage = $product['offer_image'] ?? null;
            if (Request::post('remove_offer_image') && $offerImage) {
                UploadService::delete($offerImage);
                $offerImage = null;
            }
            $file = Request::file('offer_image');
            if ($file && !empty($file['name'])) {
                try {
                    $uploaded = UploadService::store($file, 'offers');
                    if ($offerImage) {
                        UploadService::delete($offerImage);
                    }
                    $offerImage = $uploaded;
                } catch (UploadException $e) {
                    flashError('Offer image: ' . $e->getMessage());
                    redirect('/admin/offers');
                }
            }
            $endsAt = trim((string) Request::post('offer_ends_at', ''));
            $endsAt = $endsAt !== '' ? str_replace('T', ' ', $endsAt) . (strlen($endsAt) === 16 ? ':00' : '') : null;
            Product::updateOfferPricing((int) $id, $price, $original, $offerImage, $endsAt);
            flashSuccess('Offer updated.');
        }
        redirect('/admin/offers');
    }

    public function remove(string $id): void
    {
        if (csrfVerify(Request::post('csrf_token'))) {
            $product = Product::findRawById((int) $id);
            if (!empty($product['offer_image'])) {
                UploadService::delete($product['offer_image']);
            }
            Product::removeFromOffers((int) $id);
            flashSuccess('Product removed from offers.');
        }
        redirect('/admin/offers');
    }

    public function add(string $id): void
    {
        if (csrfVerify(Request::post('csrf_token'))) {
            Product::addToOffers((int) $id);
            flashSuccess('Product added to offers — set its discounted price below.');
        }
        redirect('/admin/offers');
    }
}
