<?php
declare(strict_types=1);

use Whoops\Util\TemplateHelper;

/** @var TemplateHelper $tpl */
/** @var \Whoops\Handler\PrettyPageHandler $handler */
/** @var \Whoops\Exception\FrameCollection $frames */
/** @var bool $has_frames */
/** @var string $context */
?>
<?php /* Display a code block for all frames in the stack.
       * @todo: This should PROBABLY be done on-demand, lest
       * we get 200 frames to process. */ ?>
<div class="frame-code-container <?= (!$has_frames ? 'empty' : '') ?>">
  <?php foreach ($frames as $i => $frame): ?>
    <?php $line = $frame->getLine(); ?>
      <div class="frame-code <?= ($i == 0 ) ? 'active' : '' ?>" id="frame-code-<?= $i ?>">
        <div class="frame-file">
          <?php $filePath = $frame->getFile(); ?>
          <?php if ($filePath && $editorHref = $handler->getEditorHref($filePath, (int) $line)): ?>
            <a href="<?= $tpl->escape($editorHref) ?>" class="editor-link"<?= ($handler->getEditorAjax($filePath, (int) $line) ? ' data-ajax' : '') ?>>
              Open:
              <strong><?= $tpl->breakOnDelimiter('/', $tpl->escape($filePath ?: '<#unknown>')) ?></strong>
            </a>
          <?php else: ?>
            <strong><?= $tpl->breakOnDelimiter('/', $tpl->escape($filePath ?: '<#unknown>')) ?></strong>
          <?php endif ?>
        </div>
        <?php
          // Do nothing if there's no line to work off
          if ($line !== null):

          // the $line is 1-indexed, we nab -1 where needed to account for this
          $range = $frame->getFileLines($line - 20, 40);

          // getFileLines can return null if there is no source code
          if ($range):
            $range = array_map(function ($line) { return empty($line) ? ' ' : $line;}, $range);
            $start = key($range) + 1;
            $code  = join("\n", $range);
        ?>
            <pre class="code-block line-numbers"
              data-line="<?= $line ?>"
              data-line-offset="<?= $start ?>"
              data-start="<?= $start ?>"
            ><code class="language-php"><?= $tpl->escape($code) ?></code></pre>

          <?php endif ?>
        <?php endif ?>

        <?php
          // Append comments for this frame
          $comments = $frame->getComments();
        ?>
        <div class="frame-comments <?= empty($comments) ? 'empty' : '' ?>">
          <?php foreach ($comments as $commentNo => $comment): ?>
            <?php extract($comment) ?>
            <div class="frame-comment" id="comment-<?= $i . '-' . $commentNo ?>">
              <span class="frame-comment-context"><?= $tpl->escape($context) ?></span>
              <?= $tpl->escapeButPreserveUris($comment) ?>
            </div>
          <?php endforeach ?>
        </div>

      </div>
  <?php endforeach ?>
</div>
