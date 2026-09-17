<?php
/** Forum index: retrieve, sort and filter threads. */

declare(strict_types=1);

require_once __DIR__ . '/../shared/bootstrap.php';

$sorts = [
    'activity-desc' => 'Newest activity first',
    'activity-asc'  => 'Oldest activity first',
    'created-desc'  => 'Newest thread first',
    'created-asc'   => 'Oldest thread first',
    'title-asc'     => 'Title, A to Z',
    'title-desc'    => 'Title, Z to A',
    'replies-desc'  => 'Most replies first',
];

$f = [
    'title'  => trim((string) (filter_input(INPUT_GET, 'title', FILTER_DEFAULT) ?? '')),
    'text'   => trim((string) (filter_input(INPUT_GET, 'text', FILTER_DEFAULT) ?? '')),
    'author' => (string) (filter_input(INPUT_GET, 'author', FILTER_DEFAULT) ?? ''),
    'board'  => (string) (filter_input(INPUT_GET, 'board', FILTER_DEFAULT) ?? ''),
    'sort'   => (string) (filter_input(INPUT_GET, 'sort', FILTER_DEFAULT) ?? 'activity-desc'),
];
if (!isset($sorts[$f['sort']])) {
    $f['sort'] = 'activity-desc';
}
if ($f['board'] !== '' && !isset(GF_BOARDS[$f['board']])) {
    $f['board'] = '';
}
if ($f['author'] !== '' && !user($f['author'])) {
    $f['author'] = '';
}
$page = (int) (filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);

$all = threads_query($f);
[$threads, $page, $pages, $total] = paginate($all, $page, 6);
$filtering = $f['title'] !== '' || $f['text'] !== '' || $f['author'] !== '' || $f['board'] !== '';

$page_title = 'Discussion forum';
$page_description = 'Rules arguments, army lists, work-in-progress logs and terrain builds. Start a thread or reply to one.';
require __DIR__ . '/../shared/header.php';
?>

      <nav class="breadcrumb" aria-label="<?= te('Breadcrumb') ?>">
        <ol class="breadcrumb__list">
          <li><a href="../welcome.php"><?= te('Home') ?></a></li>
          <li aria-current="page"><?= te('Forum') ?></li>
        </ol>
      </nav>

      <div class="page-head">
        <div class="page-head__text">
          <h1><?= te('Discussion forum') ?></h1>
          <p class="page-head__lede"><?= te('Rules arguments, army lists, work-in-progress logs and terrain builds. Be civil about edition changes.') ?></p>
        </div>
        <div class="page-head__actions">
<?php if (is_logged_in()): ?>
          <a class="button button--primary" href="new-thread.php"><?= te('Start a thread') ?></a>
<?php else: ?>
          <a class="button button--primary" href="../account.php?return=<?= e(rawurlencode('forum/new-thread.php')) ?>"><?= te('Log in to start a thread') ?></a>
<?php endif; ?>
        </div>
      </div>

      <section class="filter-bar" aria-labelledby="filter-heading">
        <h2 class="visually-hidden" id="filter-heading"><?= te('Sort and filter threads') ?></h2>
        <form class="filter-bar__form" action="boards.php" method="get">
          <div class="filter-bar__grid">
            <p class="field">
              <label class="field__label" for="filter-title"><?= te('Thread title contains') ?></label>
              <input type="search" id="filter-title" name="title" value="<?= e($f['title']) ?>" placeholder="<?= te('e.g. Sentinel') ?>" aria-describedby="filter-title-hint">
              <span class="field__hint" id="filter-title-hint"><?= te('Matches words in the thread title only.') ?></span>
            </p>

            <p class="field">
              <label class="field__label" for="filter-text"><?= te('Post text contains') ?></label>
              <input type="search" id="filter-text" name="text" value="<?= e($f['text']) ?>" placeholder="<?= te('e.g. cork sheet') ?>">
            </p>

            <p class="field">
              <label class="field__label" for="filter-author"><?= te('Started by') ?></label>
              <select id="filter-author" name="author">
                <option value=""<?= $f['author'] === '' ? ' selected' : '' ?>><?= te('Anyone') ?></option>
<?php foreach (collection('users') as $username => $u): ?>
                <option value="<?= e($username) ?>"<?= $f['author'] === $username ? ' selected' : '' ?>><?= e($u['name']) ?></option>
<?php endforeach; ?>
              </select>
            </p>

            <p class="field">
              <label class="field__label" for="filter-board"><?= te('Board') ?></label>
              <select id="filter-board" name="board">
                <option value=""<?= $f['board'] === '' ? ' selected' : '' ?>><?= te('All boards') ?></option>
<?php foreach (GF_BOARDS as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $f['board'] === $key ? ' selected' : '' ?>><?= te($label) ?></option>
<?php endforeach; ?>
              </select>
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
              <button type="submit" class="button button--primary"><?= te('Apply') ?></button>
              <a class="button button--quiet" href="boards.php"><?= te('Reset') ?></a>
            </div>
          </div>
        </form>
      </section>

      <div class="result-summary">
        <p><?= te('Showing %1$s of %2$s threads, %3$s.', [number(count($threads)), number($total), mb_strtolower(t($sorts[$f['sort']]))]) ?></p>
        <p><?= te('Page %1$s of %2$s', [number($page), number($pages)]) ?></p>
      </div>

      <section class="section" aria-labelledby="threads-heading">
        <h2 class="visually-hidden" id="threads-heading"><?= te('Threads') ?></h2>

<?php if (!$threads): ?>
        <div class="callout">
          <h3 class="callout__title"><?= te('No threads match') ?></h3>
          <p><?= $filtering ? te('Try fewer filters, or start the thread yourself.') : te('Nobody has started a thread yet.') ?></p>
        </div>
<?php else: ?>
        <ul class="thread-list">
<?php foreach ($threads as $t): ?>
          <li>
            <article class="thread-row">
<?php if ($t['image'] !== ''): ?>
              <img class="thread-row__thumb" src="<?= e(image_src($t['image'])) ?>" alt="<?= e($t['image_alt']) ?>">
<?php else: ?>
              <img class="thread-row__thumb" src="../assets/img/icon-forum.svg" alt="">
<?php endif; ?>
              <h3 class="thread-row__title">
                <a href="thread.php?id=<?= $t['id'] ?>"><?= e($t['title']) ?></a>
              </h3>
              <p class="thread-row__meta">
                <span><?= te('Started by') ?> <span class="byline__name"><?= e(user_name($t['author'])) ?></span> <?= time_html($t['oldest']) ?></span>
                <span><?= te('Last reply') ?> <?= time_html($t['newest']) ?></span>
                <span class="badge<?= $t['board'] === 'rules' ? ' badge--primary' : '' ?>"><?= te(GF_BOARDS[$t['board']] ?? $t['board']) ?></span>
<?php if (!empty($t['tag']) && isset(GF_THREAD_TAGS[$t['tag']])): ?>
                <span class="badge badge--info"><?= te(GF_THREAD_TAGS[$t['tag']]) ?></span>
<?php endif; ?>
<?php if (owns($t['author'])): ?>
                <span class="badge badge--primary"><?= te('Your thread') ?></span>
<?php endif; ?>
              </p>
              <p class="thread-row__stats">
                <span><?= te('%1$s replies', [number($t['replies'])]) ?></span>
                <span><?= te('%1$s views', [number((int) $t['views'])]) ?></span>
              </p>
            </article>
          </li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>

<?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="<?= te('Thread list pages') ?>">
          <ul class="pagination__list">
<?php for ($i = 1; $i <= $pages; $i++): ?>
            <li><a class="pagination__link" href="boards.php<?= e(query_with(['page' => $i])) ?>"<?= $i === $page ? ' aria-current="page"' : '' ?>><?= number($i) ?></a></li>
<?php endfor; ?>
<?php if ($page < $pages): ?>
            <li><a class="pagination__link" href="boards.php<?= e(query_with(['page' => $page + 1])) ?>"><?= te('Next page') ?></a></li>
<?php endif; ?>
          </ul>
        </nav>
<?php endif; ?>
      </section>

<?php require __DIR__ . '/../shared/footer.php'; ?>
