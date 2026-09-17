<?php
/** Blog index: retrieve, search and filter posts by every main component. */

declare(strict_types=1);

require_once __DIR__ . '/../shared/bootstrap.php';

$sorts = [
    'date-desc'     => 'Newest first',
    'date-asc'      => 'Oldest first',
    'title-asc'     => 'Title, A to Z',
    'title-desc'    => 'Title, Z to A',
    'comments-desc' => 'Most discussed',
];

$f = [
    'q'      => trim((string) (filter_input(INPUT_GET, 'q', FILTER_DEFAULT) ?? '')),
    'text'   => trim((string) (filter_input(INPUT_GET, 'text', FILTER_DEFAULT) ?? '')),
    'author' => (string) (filter_input(INPUT_GET, 'author', FILTER_DEFAULT) ?? ''),
    'tag'    => (string) (filter_input(INPUT_GET, 'tag', FILTER_DEFAULT) ?? ''),
    'from'   => trim((string) (filter_input(INPUT_GET, 'from', FILTER_DEFAULT) ?? '')),
    'to'     => trim((string) (filter_input(INPUT_GET, 'to', FILTER_DEFAULT) ?? '')),
    'sort'   => (string) (filter_input(INPUT_GET, 'sort', FILTER_DEFAULT) ?? 'date-desc'),
];
if (!isset($sorts[$f['sort']])) {
    $f['sort'] = 'date-desc';
}
if ($f['tag'] !== '' && !isset(GF_BLOG_TAGS[$f['tag']])) {
    $f['tag'] = '';
}
if ($f['author'] !== '' && !user($f['author'])) {
    $f['author'] = '';
}
foreach (['from', 'to'] as $k) {
    if ($f[$k] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $f[$k])) {
        $f[$k] = '';
    }
}
$page = (int) (filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);

$all = blog_query($f);
[$posts, $page, $pages, $total] = paginate($all, $page, 6);

$page_title = 'Blog';
$page_description = 'Painting tutorials, basing recipes and event write-ups from the Grimdark Forge staff and community.';
require __DIR__ . '/../shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="../welcome.php"><?= te('Home') ?></a></li>
          <li aria-current="page"><?= te('Blog') ?></li>
        </ol>
      </nav>

      <div class="page-head">
        <div class="page-head__text">
          <h1><?= te('Blog') ?></h1>
          <p class="page-head__lede"><?= te('Painting tutorials, basing recipes and event write-ups, mostly from the people behind the counter.') ?></p>
        </div>
        <div class="page-head__actions">
<?php if (is_logged_in()): ?>
          <a class="button button--primary" href="new-post.php"><?= te('Write a post') ?></a>
<?php else: ?>
          <a class="button button--primary" href="../account.php?return=<?= e(rawurlencode('blog/new-post.php')) ?>"><?= te('Log in to write a post') ?></a>
<?php endif; ?>
        </div>
      </div>

      <section class="filter-bar" aria-labelledby="filter-heading">
        <h2 class="visually-hidden" id="filter-heading"><?= te('Search and filter posts') ?></h2>
        <form class="filter-bar__form" action="posts.php" method="get" data-validate>
          <div class="filter-bar__grid">
            <p class="field">
              <label class="field__label" for="filter-q"><?= te('Title contains') ?></label>
              <input type="search" id="filter-q" name="q" value="<?= e($f['q']) ?>" placeholder="<?= te('e.g. highlighting') ?>" aria-describedby="filter-q-hint">
              <span class="field__hint" id="filter-q-hint"><?= te('Matches post titles and summaries.') ?></span>
            </p>

            <p class="field">
              <label class="field__label" for="filter-text"><?= te('Text contains') ?></label>
              <input type="search" id="filter-text" name="text" value="<?= e($f['text']) ?>" placeholder="<?= te('e.g. wet palette') ?>" aria-describedby="filter-text-hint">
              <span class="field__hint" id="filter-text-hint"><?= te('Searches the post body and image descriptions.') ?></span>
            </p>

            <p class="field">
              <label class="field__label" for="filter-author"><?= te('Author') ?></label>
              <select id="filter-author" name="author">
                <option value=""<?= $f['author'] === '' ? ' selected' : '' ?>><?= te('Anyone') ?></option>
<?php foreach (collection('users') as $username => $u): ?>
                <option value="<?= e($username) ?>"<?= $f['author'] === $username ? ' selected' : '' ?>><?= e($u['name']) ?></option>
<?php endforeach; ?>
              </select>
            </p>

            <p class="field">
              <label class="field__label" for="filter-tag"><?= te('Tag') ?></label>
              <select id="filter-tag" name="tag">
                <option value=""<?= $f['tag'] === '' ? ' selected' : '' ?>><?= te('All tags') ?></option>
<?php foreach (GF_BLOG_TAGS as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $f['tag'] === $key ? ' selected' : '' ?>><?= te($label) ?></option>
<?php endforeach; ?>
              </select>
            </p>

            <p class="field">
              <label class="field__label" for="filter-from"><?= te('Published from') ?></label>
              <input type="date" id="filter-from" name="from" value="<?= e($f['from']) ?>" data-type="date">
              <?= error_html([], 'from') ?>
            </p>

            <p class="field">
              <label class="field__label" for="filter-to"><?= te('Published to') ?></label>
              <input type="date" id="filter-to" name="to" value="<?= e($f['to']) ?>" data-type="date">
              <?= error_html([], 'to') ?>
            </p>

            <p class="field">
              <label class="field__label" for="filter-sort"><?= te('Sort by') ?></label>
              <select id="filter-sort" name="sort">
<?php foreach ($sorts as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $f['sort'] === $key ? ' selected' : '' ?>><?= te($label) ?></option>
<?php endforeach; ?>
              </select>
            </p>

            <div class="filter-bar__actions">
              <button type="submit" class="button button--primary"><?= te('Search') ?></button>
              <a class="button button--quiet" href="posts.php"><?= te('Reset') ?></a>
            </div>
          </div>
        </form>
      </section>

      <div class="result-summary">
        <p><?= te('Showing %1$s of %2$s posts, %3$s.', [number(count($posts)), number($total), mb_strtolower(t($sorts[$f['sort']]))]) ?></p>
        <p><?= te('Page %1$s of %2$s', [number($page), number($pages)]) ?></p>
      </div>

      <section class="section" aria-labelledby="posts-heading">
        <h2 class="visually-hidden" id="posts-heading"><?= te('Posts') ?></h2>

<?php if (!$posts): ?>
        <div class="callout">
          <h3 class="callout__title"><?= te('No posts match') ?></h3>
          <p><?= te('Try fewer filters, or write the post yourself.') ?></p>
        </div>
<?php else: ?>
        <div class="grid-cards">
<?php foreach ($posts as $p): ?>
          <article class="card<?= owns($p['author']) ? ' post--mine' : '' ?>">
<?php if ($p['image'] !== ''): ?>
            <img class="card__media" src="<?= e(image_src($p['image'])) ?>" alt="<?= e($p['image_alt']) ?>">
<?php endif; ?>
            <div class="card__body">
              <h3 class="card__title"><a href="post.php?id=<?= $p['id'] ?>"><?= e($p['title']) ?></a></h3>
              <p class="byline">
                <img class="byline__avatar" src="../<?= e(user_avatar($p['author'])) ?>" alt="">
                <span class="byline__name"><?= e(user_name($p['author'])) ?></span>
                <?= time_html($p['date']) ?>
<?php if (owns($p['author'])): ?>
                <span class="badge badge--primary"><?= te('Your post') ?></span>
<?php endif; ?>
              </p>
              <p class="card__summary"><?= e($p['summary']) ?></p>
              <ul class="tag-list">
<?php foreach ($p['tags'] as $tag): ?>
                <li><a class="tag" href="posts.php?tag=<?= e($tag) ?>"><?= te(GF_BLOG_TAGS[$tag] ?? $tag) ?></a></li>
<?php endforeach; ?>
              </ul>
              <div class="card__footer">
                <span class="text-muted text-small"><?= te('%1$s comments', [number($p['comments'])]) ?></span>
                <a href="post.php?id=<?= $p['id'] ?>"><?= te('Read the post') ?></a>
              </div>
            </div>
          </article>
<?php endforeach; ?>
        </div>
<?php endif; ?>

<?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="<?= te('Blog pages') ?>">
          <ul class="pagination__list">
<?php for ($i = 1; $i <= $pages; $i++): ?>
            <li><a class="pagination__link" href="posts.php<?= e(query_with(['page' => $i])) ?>"<?= $i === $page ? ' aria-current="page"' : '' ?>><?= number($i) ?></a></li>
<?php endfor; ?>
<?php if ($page < $pages): ?>
            <li><a class="pagination__link" href="posts.php<?= e(query_with(['page' => $page + 1])) ?>"><?= te('Next page') ?></a></li>
<?php endif; ?>
          </ul>
        </nav>
<?php endif; ?>
      </section>

<?php require __DIR__ . '/../shared/footer.php'; ?>
