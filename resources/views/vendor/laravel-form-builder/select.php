<?php if (isset($options["onlyToManager"])): ?>
    <?php if(($options["onlyToManager"] === true) && !(Auth::user()->Role->pluck("Slug")->contains(env("CUSTOMER_ROLE_SLUG")))): ?>
        <?php if ($showLabel && $showField): ?>
            <?php if (isset($options["row_start"])): ?>
                <?php if ($options["row_start"] === true): ?>
                    <div class="row" style="padding-right:15px;padding-left:15px">
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($options['wrapper'] !== false): ?>
            <div <?= $options['wrapperAttrs'] ?> >
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($showLabel && $options['label'] !== false): ?>
            <?= Form::label($name, $options['label'], $options['label_attr']) ?>
        <?php endif; ?>

        <?php if ($showField): ?>
            <?php $emptyVal = $options['empty_value'] ? ['' => $options['empty_value']] : null; ?>
            <?= Form::select($name, (array)$emptyVal + $options['choices'], $options['selected'], $options['attr']) ?>
            <?php include 'help_block.php' ?>
        <?php endif; ?>

        <?php include 'errors.php' ?>

        <?php if ($showLabel && $showField): ?>
            <?php if ($options['wrapper'] !== false): ?>
            </div>
            <?php endif; ?>
            <?php if (isset($options["row_end"])): ?>
                <?php if ($options["row_end"] === true): ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
<?php else: ?>
    <?php if ($showLabel && $showField): ?>
        <?php if (isset($options["row_start"])): ?>
            <?php if ($options["row_start"] === true): ?>
                <div class="row" style="padding-right:15px;padding-left:15px">
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($options['wrapper'] !== false): ?>
        <div <?= $options['wrapperAttrs'] ?> >
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($showLabel && $options['label'] !== false): ?>
        <?= Form::label($name, $options['label'], $options['label_attr']) ?>
    <?php endif; ?>

    <?php if ($showField): ?>
        <?php $emptyVal = $options['empty_value'] ? ['' => $options['empty_value']] : null; ?>
        <?= Form::select($name, (array)$emptyVal + $options['choices'], $options['selected'], $options['attr']) ?>
        <?php include 'help_block.php' ?>
    <?php endif; ?>

    <?php include 'errors.php' ?>

    <?php if ($showLabel && $showField): ?>
        <?php if ($options['wrapper'] !== false): ?>
        </div>
        <?php endif; ?>
        <?php if (isset($options["row_end"])): ?>
            <?php if ($options["row_end"] === true): ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
