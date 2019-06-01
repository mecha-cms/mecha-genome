<?php namespace _\route;

$GLOBALS['page'] = new \Page;
$GLOBALS['pager'] = new \Pager\Pages;
$GLOBALS['pages'] = new \Anemon;
$GLOBALS['parent'] = new \Page;
$GLOBALS['t'] = new \Anemon([$config->title], ' &#x00B7; ');

function page($form) {
    global $config, $language, $url;
    $i = ($url->i ?? 1) - 1;
    // Load default page state(s)…
    $state = \extend('page');
    // Prevent directory traversal attack
    // <https://en.wikipedia.org/wiki/Directory_traversal_attack>
    $path = \str_replace('../', "", \urldecode($this[0]));
    // Default home path
    $p = \trim($path === "" ? $state['path'] : $path, '/');
    if ($i < 1 && $path === $state['path'] && !$url->query) {
        \Guard::kick(""); // Redirect to home page
    }
    $folder = \rtrim(PAGE . DS . \strtr($p, '/', DS), DS);
    if ($file = \File::exist([
        $folder . '.page',
        $folder . '.archive'
    ])) {
        $k = PAGE;
        $page = new \Page($file);
        $sort = $page['sort'] ?? [1, 'path'];
        $chunk = $page['chunk'] ?? 5;
        foreach (\explode('/', '/' . $path) as $v) {
            $k .= $v ? DS . $v : "";
            if ($f = \File::exist([
                $k . '.page',
                $k . '.archive'
            ])) {
                $f = new \Page($f);
                $sort = $f['sort'] ?? $sort;
                $chunk = $f['chunk'] ?? $chunk;
            }
            // Load user function(s) from the current page folder if any,
            // stacked from the parent page(s)
            if (\is_file($fn = $k . DS . 'index.php')) {
                \call_user_func(function() use($fn) {
                    extract($GLOBALS, EXTR_SKIP);
                    require $fn;
                });
            }
        }
        $parent_path = \dirname($path);
        $parent_folder = \dirname($folder);
        if ($parent_file = \File::exist([
            $parent_folder . '.page', // `.\lot\page\parent-slug.page`
            $parent_folder . '.archive', // `.\lot\page\parent-slug.archive`
            $parent_folder . DS . '.page', // `.\lot\page\parent-slug\.page`
            $parent_folder . DS . '.archive' // `.\lot\page\parent-slug\.archive`
        ])) {
            $parent_page = new \Page($parent_file);
            // Inherit parent’s `sort` and `chunk` property where possible
            $sort = $parent_page['sort'] ?? $sort;
            $chunk = $parent_page['chunk'] ?? $chunk;
            $parent_pages = \Get::pages($parent_folder, 'page', $sort, 'slug')->get();
        }
        $pager = new \Pager\Page($parent_pages ?? [], $page['slug'], $url . '/' . $parent_path);
        $GLOBALS['page'] = $page;
        $GLOBALS['pager'] = $pager;
        $GLOBALS['parent'] = $parent_page ?? new \Page;
        $GLOBALS['t'][] = $page->title;
        \Config::set([
            'chunk' => $chunk, // Inherit page’s `chunk` property
            'has' => [
                'next' => !!$pager->next,
                'parent' => !!$pager->parent,
                'prev' => !!$pager->prev
            ],
            'sort' => $sort // Inherit page’s `sort` property
        ]);
        $pages = \Get::pages($folder, 'page', $sort, 'path');
        // No page(s) means “page” mode
        if ($pages->count() === 0 || \is_file($folder . DS . '.' . $page['x'])) {
            $this->status(200);
            $this->view('page/' . $p . '/' . ($i + 1));
        }
        // Create pager for “pages” mode
        $pager = new \Pager\Pages($pages->get(), [$chunk, $i], $url . '/' . $p);
        $pages = $pages->chunk($chunk, $i)->map(function($v) {
            return new \Page($v);
        });
        if ($pages->count() > 0) {
            \Config::set('has', [
                'next' => !!$pager->next,
                'parent' => !!$pager->parent,
                'prev' => !!$pager->prev,
            ]);
            $GLOBALS['page'] = $page;
            $GLOBALS['pager'] = $pager;
            $GLOBALS['pages'] = $pages;
            $this->status(200);
            $this->view('pages/' . $p . '/' . ($i + 1));
        }
        \Config::set('is.error', 404);
        \Config::set('has', [
            'next' => false,
            'parent' => false,
            'prev' => false
        ]);
        $GLOBALS['t'][] = $language->isError;
        $this->view('404/' . $p . '/' . ($i + 1));
        /*
        // Redirect to parent page if user tries to access the placeholder page…
        if (\basename($folder) === "" && \is_file($folder . '.' . $page['x'])) {
            \Guard::kick($parent_path);
        }
        $this->view('page/' . $p . '/' . ($i + 1));
        */
    } else {
        \Config::set('is.error', 404);
        $GLOBALS['t'][] = $language->isError;
        $this->view('404/' . $p . '/' . ($i + 1));
    }
}

\Route::set(['*', ""], __NAMESPACE__ . "\\page", 20);