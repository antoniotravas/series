<?php
$v = fn(string $k, $d = '') => $form[$k] ?? $d;
$sets = $form['sets'] ?? [];
if (!$sets) {
    $sets = [['distance_m' => '', 'rest' => '', 'rest_type' => 'stand', 'target' => '', 'reps' => array_fill(0, 6, '')]];
}
$quickDistances = [200, 300, 400, 500, 600, 800, 1000, 1200, 1600, 2000];

/** Bloco de uma série. $i pode ser '__i__' no template. */
$setBlock = function ($i, array $s, ?string $error) use ($quickDistances) {
    $reps = array_values((array)($s['reps'] ?? []));
    if (!$reps) {
        $reps = array_fill(0, 6, '');
    }
    $hr = array_values((array)($s['hr'] ?? []));   // FC média e máxima de cada repetição (vêm do GPX)
    $hrMax = array_values((array)($s['hr_max'] ?? []));
    $n = is_int($i) ? $i + 1 : '__n__';
    ?>
    <fieldset class="setform" data-set>
      <legend class="setform__legend">
        <span data-set-label><?= e(t('w.set', ['n' => $n])) ?></span>
        <button type="button" class="btn btn--icon" data-remove-set aria-label="<?= e(t('w.set.remove')) ?>" title="<?= e(t('w.set.remove')) ?>">
          <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M4 4l8 8M12 4l-8 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </button>
      </legend>
      <?php if ($error): ?><p class="field__error" role="alert"><?= e($error) ?></p><?php endif; ?>
      <div class="setform__grid">
        <div class="field field--sm">
          <label for="s<?= $i ?>-count"><?= e(t('w.set.reps')) ?></label>
          <input id="s<?= $i ?>-count" type="number" min="1" max="<?= MAX_REPS ?>" inputmode="numeric" value="<?= count($reps) ?>" data-rep-count>
        </div>
        <div class="field field--sm">
          <label for="s<?= $i ?>-dist"><?= e(t('w.set.distance')) ?></label>
          <input id="s<?= $i ?>-dist" name="sets[<?= $i ?>][distance_m]" type="number" min="50" max="42195" step="1" inputmode="numeric" list="quick-distances" value="<?= e((string)($s['distance_m'] ?? '')) ?>" data-distance required>
        </div>
        <div class="field field--sm">
          <label for="s<?= $i ?>-rest"><?= e(t('w.set.rest')) ?></label>
          <input id="s<?= $i ?>-rest" name="sets[<?= $i ?>][rest]" inputmode="decimal" placeholder="<?= e(t('w.set.rest_ph')) ?>" value="<?= e((string)($s['rest'] ?? '')) ?>" data-time>
        </div>
        <div class="field field--sm">
          <label for="s<?= $i ?>-rtype"><?= e(t('w.set.rest_type')) ?></label>
          <select id="s<?= $i ?>-rtype" name="sets[<?= $i ?>][rest_type]">
            <?php foreach (REST_TYPES as $rt): ?>
              <option value="<?= $rt ?>" <?= ($s['rest_type'] ?? 'stand') === $rt ? 'selected' : '' ?>><?= e(t('w.rest.' . $rt)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field field--sm">
          <label for="s<?= $i ?>-target"><?= e(t('w.set.target')) ?></label>
          <input id="s<?= $i ?>-target" name="sets[<?= $i ?>][target]" inputmode="decimal" placeholder="<?= e(t('w.set.target_ph')) ?>" value="<?= e((string)($s['target'] ?? '')) ?>" data-time data-target>
        </div>
      </div>
      <div class="chips" aria-hidden="true">
        <?php foreach ($quickDistances as $qd): ?><button type="button" class="chip" tabindex="-1" data-quick="<?= $qd ?>"><?= $qd ?></button><?php endforeach; ?>
      </div>
      <p class="setform__label"><?= e(t('w.set.times')) ?></p>
      <ol class="reps" data-reps>
        <?php foreach ($reps as $k => $r): ?>
        <li class="rep">
          <label class="rep__n" for="s<?= $i ?>-r<?= $k ?>"><?= $k + 1 ?></label>
          <input id="s<?= $i ?>-r<?= $k ?>" name="sets[<?= $i ?>][reps][]" inputmode="decimal" autocomplete="off" placeholder="0:00.0" value="<?= e((string)$r) ?>" data-rep aria-label="<?= e(t('w.rep', ['n' => $k + 1])) ?>">
          <input type="hidden" name="sets[<?= $i ?>][hr][]" value="<?= e((string)($hr[$k] ?? '')) ?>" data-rep-hr>
          <input type="hidden" name="sets[<?= $i ?>][hr_max][]" value="<?= e((string)($hrMax[$k] ?? '')) ?>" data-rep-hr-max>
        </li>
        <?php endforeach; ?>
      </ol>
      <output class="setform__live" data-live aria-live="polite"></output>
    </fieldset>
    <?php
};
?>
<?php $athlete = $athlete ?? null; ?>
<header class="page-head">
  <div>
    <h1><?= e($title) ?></h1>
    <?php if ($athlete): ?>
    <p class="for-athlete">
      <?php view('partials/avatar', ['u' => $athlete, 'size' => 'sm']); ?>
      <span><?= t('w.for_athlete', ['name' => '<strong>' . e($athlete['display_name']) . '</strong>']) ?></span>
    </p>
    <?php endif; ?>
  </div>
</header>

<?php if ($errors): ?><div class="flash flash--error" role="alert"><?= e(t('error.form')) ?></div><?php endif; ?>

<form method="post" class="form wform" novalidate data-workout-form>
  <?= csrf_field() ?>
  <datalist id="quick-distances">
    <?php foreach ($quickDistances as $qd): ?><option value="<?= $qd ?>"><?php endforeach; ?>
  </datalist>
  <datalist id="known-locations">
    <?php foreach ($locations as $l): ?><option value="<?= e($l) ?>"><?php endforeach; ?>
  </datalist>

  <section class="gpxfill" data-gpx-fill hidden
           data-url="<?= url('gpx_detect.php') ?>"
           data-loading="<?= e(t('gpx.loading')) ?>"
           data-replace="<?= e(t('gpx.replace')) ?>"
           data-ignored="<?= e(t('gpx.ignored')) ?>"
           data-error="<?= e(t('error.gpx_invalid')) ?>">
    <div class="gpxfill__row">
      <label class="btn btn--ghost btn--sm" for="gpx-auto"><?= e(t('gpx.button')) ?></label>
      <input id="gpx-auto" type="file" accept=".gpx,application/gpx+xml" class="sr-only" data-gpx-input>
      <p class="muted small"><?= e(t('gpx.lead')) ?></p>
    </div>
    <div class="gpxfill__link">
      <label for="gpx-link" class="small"><?= e(t('gpx.link_label')) ?></label>
      <div class="gpxfill__linkrow">
        <input id="gpx-link" type="url" inputmode="url" placeholder="https://api.sports-tracker.com/…" data-gpx-link>
        <button type="button" class="btn btn--ghost btn--sm" data-gpx-link-go><?= e(t('gpx.link_btn')) ?></button>
      </div>
      <p class="field__hint"><?= e(t('gpx.link_hint')) ?></p>
    </div>
    <div class="gpxfill__result" data-gpx-result aria-live="polite"></div>
  </section>

  <section class="wform__meta">
    <div class="field field--wide">
      <label for="title"><?= e(t('w.title')) ?></label>
      <input id="title" name="title" maxlength="120" placeholder="<?= e(t('w.title_ph')) ?>" value="<?= e((string)$v('title')) ?>">
    </div>
    <div class="field">
      <label for="workout_date"><?= e(t('w.date')) ?></label>
      <input id="workout_date" name="workout_date" type="date" required max="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= e((string)$v('workout_date')) ?>"<?= field_attrs($errors, 'workout_date') ?>>
      <?= field_error($errors, 'workout_date') ?>
    </div>
    <div class="field">
      <label for="start_time"><?= e(t('w.time')) ?></label>
      <input id="start_time" name="start_time" type="time" value="<?= e((string)$v('start_time')) ?>"<?= field_attrs($errors, 'start_time') ?>>
      <?= field_error($errors, 'start_time') ?>
    </div>
    <div class="field">
      <label for="location"><?= e(t('w.location')) ?></label>
      <input id="location" name="location" maxlength="120" list="known-locations" placeholder="<?= e(t('w.location_ph')) ?>" value="<?= e((string)$v('location')) ?>">
      <input type="hidden" name="lat" value="<?= e((string)$v('lat')) ?>" data-lat>
      <input type="hidden" name="lon" value="<?= e((string)$v('lon')) ?>" data-lon>
    </div>
    <div class="field">
      <label for="venue_type"><?= e(t('w.venue')) ?></label>
      <select id="venue_type" name="venue_type">
        <?php foreach (VENUE_TYPES as $vt): ?>
          <option value="<?= $vt ?>" <?= $v('venue_type', 'outdoor') === $vt ? 'selected' : '' ?>><?= e(t('w.venue.' . $vt)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="visibility"><?= e(t('vis.label')) ?></label>
      <select id="visibility" name="visibility" aria-describedby="visibility-hint">
        <?php foreach (VISIBILITIES as $vis): ?>
          <option value="<?= $vis ?>" <?= $v('visibility', 'private') === $vis ? 'selected' : '' ?>><?= e(t('vis.' . $vis)) ?></option>
        <?php endforeach; ?>
      </select>
      <p class="field__hint" id="visibility-hint"><?= e(t('vis.hint')) ?></p>
    </div>
  </section>

  <section class="wform__sets" aria-labelledby="sets-h">
    <h2 id="sets-h"><?= e(t('w.sets')) ?></h2>
    <p class="muted small"><?= e(t('w.sets.lead')) ?></p>
    <?php if (!empty($errors['sets'])): ?><p class="field__error" role="alert"><?= e($errors['sets']) ?></p><?php endif; ?>
    <div data-sets>
      <?php foreach (array_values($sets) as $i => $s) { $setBlock($i, $s, $errors["sets.$i"] ?? null); } ?>
    </div>
    <template data-set-template><?php $setBlock('__i__', ['reps' => array_fill(0, 6, '')], null); ?></template>
    <button type="button" class="btn btn--ghost" data-add-set><?= e(t('w.set.add')) ?></button>
  </section>

  <details class="wform__more" <?= ($v('notes') || $v('rpe') || $v('weather')) ? 'open' : '' ?>>
    <summary><?= e(t('w.notes')) ?>, <?= e(mb_strtolower(t('w.rpe'))) ?>, <?= e(mb_strtolower(t('w.weather'))) ?></summary>
    <div class="wform__meta">
      <div class="field">
        <label for="rpe"><?= e(t('w.rpe')) ?></label>
        <select id="rpe" name="rpe">
          <option value=""><?= e(t('w.rpe.none')) ?></option>
          <?php for ($r = 1; $r <= 10; $r++): ?><option value="<?= $r ?>" <?= (string)$v('rpe') === (string)$r ? 'selected' : '' ?>><?= $r ?></option><?php endfor; ?>
        </select>
      </div>
      <div class="field">
        <label for="weather"><?= e(t('w.weather')) ?></label>
        <input id="weather" name="weather" maxlength="60" placeholder="<?= e(t('w.weather_ph')) ?>" value="<?= e((string)$v('weather')) ?>">
      </div>
      <div class="field">
        <label for="temperature_c"><?= e(t('w.temp')) ?></label>
        <input id="temperature_c" name="temperature_c" type="number" min="-30" max="50" inputmode="numeric" value="<?= e((string)$v('temperature_c')) ?>">
      </div>
      <div class="field field--wide">
        <label for="notes"><?= e(t('w.notes')) ?></label>
        <textarea id="notes" name="notes" rows="4" placeholder="<?= e(t('w.notes_ph')) ?>"><?= e((string)$v('notes')) ?></textarea>
      </div>
    </div>
  </details>

  <div class="wform__submit">
    <button class="btn btn--primary btn--lg" type="submit"><?= e(t('w.save')) ?></button>
    <a class="link" href="<?= url($existing ? 'workout.php?id=' . (int)$existing['id'] : ($athlete ? 'workouts.php?u=' . (int)$athlete['id'] : 'workouts.php')) ?>"><?= e(t('w.cancel')) ?></a>
  </div>
</form>
