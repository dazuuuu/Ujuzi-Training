<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Services\PageBuilder;
use App\Services\UploadException;
use App\Services\UploadService;

/**
 * The public page builder: Super Admin arranges each public page's sections,
 * edits their words, pictures and sizes, and the navbar and footer, with a
 * live preview beside the editor.
 */
class PagesController extends BaseAdminController
{
    public function index(): void
    {
        $page = (string) Request::query('page', '');
        if (!PageBuilder::isPage($page)) {
            $this->manager();
            return;
        }
        View::render('admin.pages.index', [
            'pageTitle' => 'Public pages',
            'activeNav' => 'pages',
            'page' => $page,
            'sections' => PageBuilder::sections($page),
            'site' => PageBuilder::site(),
            'defaults' => PageBuilder::defaults($page),
            'schema' => PageBuilder::schema(),
            'previewToken' => $this->previewToken(),
        ]);
    }

    /** Saves the page's sections and the navbar & footer. */
    public function save(): void
    {
        [$page, $sections, $site] = $this->posted();
        PageBuilder::save($page, $sections);
        PageBuilder::saveSite($site);
        $this->respond(['ok' => true, 'message' => PageBuilder::pages()[$page] . ' saved.']);
    }

    /** Keeps unsaved changes for the preview frame (seen only by this admin, with ?pb_preview=1). */
    public function preview(): void
    {
        [$page, $sections, $site] = $this->posted();
        PageBuilder::setPreviewDraft($this->previewToken(), $page, PageBuilder::normalizeSections($page, $sections), PageBuilder::normalizeSite($site));
        $this->respond(['ok' => true]);
    }

    /** Every page of the website in one list: built-in, custom and the portals. */
    private function manager(): void
    {
        View::render('admin.pages.manager', [
            'pageTitle' => 'Pages',
            'activeNav' => 'pages',
            'customPages' => PageBuilder::customPages(),
        ]);
    }

    /** A new page, opened straight in the editor. */
    public function create(): void
    {
        $this->formCsrf();
        $title = trim((string) Request::post('title', ''));
        if ($title === '') {
            flashError('Give the page a title.');
            redirect('/admin/pages');
        }
        $slug = PageBuilder::slugFor((string) Request::post('slug', '') ?: $title);
        $pages = PageBuilder::customPages();
        $pages[$slug] = ['title' => mb_substr($title, 0, 120), 'description' => '', 'in_nav' => Request::post('in_nav') === '1', 'published' => false];
        PageBuilder::saveCustomPages($pages);
        $this->syncNav($slug, $pages[$slug]);
        flashSuccess('Page created. Build it, then switch it to published under Pages.');
        redirect('/admin/pages?page=c-' . $slug);
    }

    /** Title, description, published and in-navbar for a custom page. */
    public function updateMeta(string $slug): void
    {
        $this->formCsrf();
        $pages = PageBuilder::customPages();
        if (!isset($pages[$slug])) {
            flashError('That page could not be found.');
            redirect('/admin/pages');
        }
        $pages[$slug]['title'] = mb_substr(trim((string) Request::post('title', '')) ?: $pages[$slug]['title'], 0, 120);
        $pages[$slug]['description'] = mb_substr(trim((string) Request::post('description', '')), 0, 300);
        $pages[$slug]['published'] = Request::post('published') === '1';
        $pages[$slug]['in_nav'] = Request::post('in_nav') === '1';
        PageBuilder::saveCustomPages($pages);
        $this->syncNav($slug, $pages[$slug]);
        flashSuccess('Page details saved.');
        redirect('/admin/pages');
    }

    public function delete(string $slug): void
    {
        $this->formCsrf();
        $pages = PageBuilder::customPages();
        if (isset($pages[$slug])) {
            unset($pages[$slug]);
            PageBuilder::saveCustomPages($pages);
            PageBuilder::reset('c-' . $slug);
            $this->syncNav($slug, null);
            flashSuccess('Page deleted.');
        }
        redirect('/admin/pages');
    }

    /** Adds or removes the page's link in the navbar to match its settings. */
    private function syncNav(string $slug, ?array $meta): void
    {
        $site = PageBuilder::site();
        $url = '/p/' . $slug;
        $links = array_values(array_filter($site['nav_links'], static fn(array $l): bool => ($l['url'] ?? '') !== $url));
        if ($meta && $meta['in_nav'] && $meta['published']) {
            $links[] = ['label' => $meta['title'], 'url' => $url];
        }
        $site['nav_links'] = $links;
        PageBuilder::saveSite($site);
    }

    private function formCsrf(): void
    {
        if (!csrfVerify((string) Request::post('csrf_token', ''))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/pages');
        }
    }

    public function upload(): void
    {
        if (!csrfVerify((string) Request::post('csrf_token', ''))) {
            $this->respond(['ok' => false, 'message' => 'Your session expired. Reload the page.'], 419);
        }
        $file = Request::file('image');
        if (!$file) {
            $this->respond(['ok' => false, 'message' => 'Choose a picture.'], 422);
        }
        try {
            $path = UploadService::store($file, 'pages');
        } catch (UploadException $e) {
            $this->respond(['ok' => false, 'message' => $e->getMessage()], 422);
        }
        $this->respond(['ok' => true, 'path' => $path, 'url' => imageUrl($path)]);
    }

    /** This admin's preview token (see PageBuilder::previewToken()). */
    private function previewToken(): string
    {
        if (empty($_SESSION['page_builder_token'])) {
            $_SESSION['page_builder_token'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['page_builder_token'];
    }

    /** @return array{0: string, 1: array, 2: array} */
    private function posted(): array
    {
        $data = Request::json();
        if (!csrfVerify((string) ($data['csrf_token'] ?? ''))) {
            $this->respond(['ok' => false, 'message' => 'Your session expired. Reload the page.'], 419);
        }
        $page = (string) ($data['page'] ?? '');
        if (!PageBuilder::isPage($page)) {
            $this->respond(['ok' => false, 'message' => 'Unknown page.'], 422);
        }
        return [$page, is_array($data['sections'] ?? null) ? $data['sections'] : [], is_array($data['site'] ?? null) ? $data['site'] : []];
    }

    private function respond(array $payload, int $status = 200): never
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($payload);
        exit;
    }
}
