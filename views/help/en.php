<?php
$toc = [
    'start'     => 'Getting started',
    'log'       => 'Logging a workout',
    'gpx'       => 'Using your watch GPX file',
    'analyse'   => 'Analysing a workout',
    'find'      => 'Finding and organising workouts',
    'dashboard' => 'Dashboard and records',
    'excel'     => 'Import and export',
    'share'     => 'Sharing workouts',
    'network'   => 'Athlete network',
    'coach'     => 'I am a coach',
    'athlete'   => 'I have a coach',
    'profile'   => 'Profile and account',
    'faq'       => 'FAQ',
];

$body = function (): void { ?>

<section id="start" class="help__section">
  <h2>Getting started</h2>
  <p>Séries is a training log for track runners. You record every rep with its time, and the app works out averages, pace, consistency and personal bests.</p>

  <article class="usecase">
    <h3>I want to create an account</h3>
    <ol>
      <li>Press <b>Sign up</b> at the top right.</li>
      <li>Enter the name others will see, your email and a password of at least 8 characters.</li>
      <li>Or use <b>Continue with Google</b> to sign in with your Gmail account, no password needed.</li>
    </ol>
  </article>

  <article class="usecase">
    <h3>I forgot my password</h3>
    <ol>
      <li>On the log in screen, press <b>Forgot password</b>.</li>
      <li>Enter your account email. You will get a link valid for 60 minutes.</li>
      <li>Open the link and choose a new password.</li>
    </ol>
  </article>

  <article class="usecase">
    <h3>I want to change the language</h3>
    <p>At the bottom of any page pick <b>PT</b> or <b>EN</b>. When you are logged in, the choice is saved to your account.</p>
  </article>

  <article class="usecase">
    <h3>Where everything is</h3>
    <ul>
      <li><b>Dashboard</b>: summary, records and charts.</li>
      <li><b>Workouts</b>: list with filters, import and export.</li>
      <li><b>Network</b>: people, requests and shared workouts. The number next to it shows requests waiting for you.</li>
      <li><b>Athletes</b>: only shown to coaches.</li>
      <li><b>Your photo or initials</b>: opens a small menu with <b>View profile</b>, <b>User guide</b> and <b>Log out</b>.</li>
      <li><b>Log workout</b>: the red button. On phones it stays in the bottom right corner.</li>
    </ul>
  </article>
</section>

<section id="log" class="help__section">
  <h2>Logging a workout</h2>

  <article class="usecase">
    <h3>I ran 10 × 500 m with 70 s recovery</h3>
    <ol>
      <li>Press <b>Log workout</b>.</li>
      <li>Check the <b>date</b> and <b>time</b>. Optionally add a title and the place.</li>
      <li>In <b>Set 1</b> enter <b>10</b> reps and <b>500</b> as the distance (m). Ten time boxes appear.</li>
      <li>In <b>Recovery</b> enter <kbd>1:10</kbd> or <kbd>70</kbd>, and choose whether you stood, walked or jogged.</li>
      <li>Type each rep time, for example <kbd>1:32.4</kbd> or <kbd>92.4</kbd>. The average, pace and deviation update as you type.</li>
      <li>Press <b>Save workout</b>.</li>
    </ol>
    <p class="tip">Use Tab, or "next" on your phone keyboard, to jump to the next box.</p>
  </article>

  <article class="usecase">
    <h3>The workout had several sets (e.g. 4 × 400 m + 4 × 200 m)</h3>
    <ol>
      <li>Fill in the first set with 4 × 400.</li>
      <li>Press <b>Add set</b> and fill in 4 × 200.</li>
      <li>Each set has its own recovery, target and stats. Use <b>Remove set</b> to drop one.</li>
    </ol>
  </article>

  <article class="usecase">
    <h3>I had a target time for each rep</h3>
    <p>Enter it in the set's <b>Target time</b>, e.g. <kbd>1:32</kbd>. The workout page then shows each rep against the target.</p>
  </article>

  <article class="usecase">
    <h3>I want to note conditions and how it felt</h3>
    <p>Below the sets there are optional fields: <b>Surface</b>, <b>Conditions</b>, <b>Temperature</b>, <b>Perceived effort</b> from 1 to 10, and <b>Notes</b>.</p>
  </article>

  <article class="usecase">
    <h3>I repeat the same session every week</h3>
    <ol>
      <li>Open last week's workout.</li>
      <li>Press <b>Duplicate</b>. A new workout opens with the same sets and today's date.</li>
      <li>Enter the new times and save.</li>
    </ol>
  </article>

  <article class="usecase">
    <h3>I typed a time wrong</h3>
    <p>Open the workout, press <b>Edit</b>, fix it and save.</p>
  </article>
</section>

<section id="gpx" class="help__section">
  <h2>Using your watch GPX file</h2>

  <article class="usecase">
    <h3>I want to fill in the times from my watch</h3>
    <ol>
      <li>Export the activity as GPX from your watch app (Garmin Connect, Coros, Strava…).</li>
      <li>In <b>Log workout</b>, press <b>Fill in from GPX</b> and pick the file.</li>
      <li>The app finds the reps by speed and rounds them to track distances (294 m becomes 300 m). Warm-up and cool-down are ignored.</li>
      <li>Date, time, place and, if your watch recorded them, average heart rate and temperature are filled in.</li>
      <li><b>Check the times</b> before saving: GPS on a track is off by a few metres.</li>
    </ol>
    <p class="tip">The GPX file is not stored on the server. Only the times you confirm are saved.</p>
  </article>

  <article class="usecase">
    <h3>The place name is wrong</h3>
    <p>Change it in the <b>Location</b> field. Next time you train there, the app will use your name.</p>
  </article>
</section>

<section id="analyse" class="help__section">
  <h2>Analysing a workout</h2>

  <article class="usecase">
    <h3>I want to know whether I was consistent</h3>
    <p>Open the workout from <b>Workouts</b>. Each set shows:</p>
    <ul>
      <li><b>Average</b>, <b>Best</b>, <b>Worst</b> and <b>Pace</b> per km.</li>
      <li><b>Deviation</b>: how much your times varied. Lower means more consistent.</li>
      <li>A chart of each rep against the average: green and to the right when faster, red and to the left when slower.</li>
      <li>The <b>Speed</b> button shows the speed of each rep instead.</li>
      <li>A gold <b>Personal best</b> mark on any rep that beat your best for that distance.</li>
    </ul>
  </article>
</section>

<section id="find" class="help__section">
  <h2>Finding and organising workouts</h2>

  <article class="usecase">
    <h3>I want every 1000 m from this year</h3>
    <ol>
      <li>Open <b>Workouts</b>.</li>
      <li>Pick <b>Distance</b> 1000 and the <b>Year</b>. You can also filter by <b>Month</b>, <b>Location</b>, free text and <b>Status</b>.</li>
      <li>Press <b>Filter</b>. <b>Clear filters</b> shows everything again.</li>
    </ol>
  </article>

  <article class="usecase">
    <h3>A session went badly and I don't want it in my records</h3>
    <p>Open it and press <b>Deactivate</b>. It stays saved but no longer counts on the dashboard or for personal bests. Press <b>Reactivate</b> to count it again.</p>
  </article>

  <article class="usecase">
    <h3>I want to change many workouts at once</h3>
    <ol>
      <li>In <b>Workouts</b>, tick each workout, or the box at the top to select the whole page.</li>
      <li>In the bar that appears choose: deactivate, reactivate, delete or change <b>Who can see it</b>.</li>
    </ol>
    <p class="tip">Deleting cannot be undone. If in doubt, deactivate.</p>
  </article>
</section>

<section id="dashboard" class="help__section">
  <h2>Dashboard and records</h2>

  <article class="usecase">
    <h3>I want to see my progress</h3>
    <p>The <b>Dashboard</b> opens after you log in. It shows:</p>
    <ul>
      <li><b>Best times</b> for each distance.</li>
      <li>Total workouts, km in reps and reps, plus the last 30 days.</li>
      <li>How many <b>weeks in a row</b> you have trained.</li>
      <li><b>Weekly volume</b> for the last 12 weeks.</li>
      <li><b>Progress</b>: average and best rep of each workout at one distance. Change the distance with the selector. It needs at least 2 workouts at that distance.</li>
      <li><b>Most run distances</b> and <b>Recent workouts</b>.</li>
    </ul>
  </article>
</section>

<section id="excel" class="help__section">
  <h2>Import and export</h2>

  <article class="usecase">
    <h3>I have years of sessions in an Excel sheet</h3>
    <ol>
      <li>In <b>Workouts</b>, press <b>Import from Excel</b>.</li>
      <li>Pick the .xlsx file (up to 5 MB). Every tab is read.</li>
      <li>You get a list of the workouts found. Days where you already have a workout are flagged.</li>
      <li>Pick the ones you want and press <b>Import selected</b>.</li>
    </ol>
    <p class="tip">The import page explains the sheet layout: the distance in the tab name (e.g. "400m") and a header row "Date | 1 | 2 | 3 … | Rest".</p>
  </article>

  <article class="usecase">
    <h3>I want my data in a spreadsheet</h3>
    <p>In <b>Workouts</b> or <b>View profile</b>, press <b>Export workouts (CSV)</b>. It opens in Excel, Numbers or Google Sheets.</p>
  </article>
</section>

<section id="share" class="help__section">
  <h2>Sharing workouts</h2>

  <article class="usecase">
    <h3>I want others to see a workout</h3>
    <p>Each workout has a <b>Who can see it</b> field in its form:</p>
    <ul>
      <li><b>Only me</b>: private.</li>
      <li><b>Connections</b>: the people you are connected to in the Network.</li>
      <li><b>Public</b>: any user of the app, on your profile.</li>
    </ul>
    <p>A new workout uses the same option as the last one you logged. To change several at once, use the selection in <b>Workouts</b>.</p>
    <p class="tip">Your coaches always see all your workouts, including "Only me" ones.</p>
  </article>

  <article class="usecase">
    <h3>I want to post a workout on Instagram or another social network</h3>
    <ol>
      <li>Open the workout and press <b>Share</b>.</li>
      <li>Pick the format: <b>Post</b> (4:5, for the feed) or <b>Story</b> (9:16).</li>
      <li>Pick what to show: the speed of each rep as <b>Bars</b> or <b>Lines</b>, the <b>Times</b> of every rep, or <b>Nothing</b>. You can also tick or untick the avatar and name, the date and place and the numbers (average, fastest, volume and reps). Personal bests set in the workout always show, in gold.</li>
      <li>Pick the colours: <b>Night</b>, <b>Tartan</b>, <b>Light</b>, <b>Grass</b> or <b>Blue</b>. Tick <b>Transparent background</b> to place the image over one of your photos (the colours then apply to the text).</li>
      <li>On a phone, press <b>Share image</b> and choose the app. On a computer, press <b>Copy image</b> and paste it anywhere (Ctrl+V), or <b>Download PNG</b>.</li>
    </ol>
    <p>The image shows your avatar, the average, the fastest rep, the volume, the day's personal bests and the chart or the times, with the address www.series.corridas.eu at the bottom.</p>
    <p class="tip">Only the author can make the image. Coaches don't see the button on their athletes' workouts.</p>
  </article>
</section>

<section id="network" class="help__section">
  <h2>Athlete network</h2>

  <article class="usecase">
    <h3>I want to connect with a training partner</h3>
    <ol>
      <li>Open <b>Network</b> and type a name or club in <b>Find people</b>.</li>
      <li>Press <b>Connect</b> in the list or on their profile.</li>
      <li>Once they accept, you both see each other's "Connections" workouts.</li>
    </ol>
  </article>

  <article class="usecase">
    <h3>Someone sent me a request</h3>
    <p>A number appears next to <b>Network</b>. Open it and, under <b>Requests received</b>, press <b>Accept</b> or <b>Reject</b>. Rejected people are not notified.</p>
  </article>

  <article class="usecase">
    <h3>I want to see what others are training</h3>
    <p><b>Network → Recent activity</b> lists your connections' shared workouts. Press a name to open their profile and the workouts they share with you.</p>
  </article>

  <article class="usecase">
    <h3>I no longer want to be connected to someone</h3>
    <p>In your <b>Connections</b> list, or on their profile, press <b>Remove</b>.</p>
  </article>
</section>

<section id="coach" class="help__section">
  <h2>I am a coach</h2>
  <p>A coach sees <b>all</b> of their athletes' workouts, including private ones, and each athlete's dashboard. Every athlete has to accept the link.</p>

  <article class="usecase">
    <h3>I want to turn on the coach profile</h3>
    <ol>
      <li>Press your photo at the top right, then <b>View profile</b>.</li>
      <li>Press <b>Edit profile</b>, tick <b>I am a coach</b> and press <b>Save profile</b>.</li>
      <li>The <b>Athletes</b> menu appears, and a "Coach" badge by your name. On phones, open Athletes from the <b>Network</b> page.</li>
    </ol>
  </article>

  <article class="usecase">
    <h3>I want to add an athlete</h3>
    <ol>
      <li>Find the athlete in <b>Network</b> and open their profile.</li>
      <li>Press <b>Invite as athlete</b>.</li>
      <li>Once they accept, they show up in <b>Athletes</b>.</li>
    </ol>
    <p class="tip">Athletes can also ask you. The request shows in <b>Network → Requests received</b>, where you press <b>Accept as athlete</b>.</p>
  </article>

  <article class="usecase">
    <h3>I want to check on my athletes</h3>
    <ol>
      <li>Open <b>Athletes</b>. The table shows each athlete's last workout, and their workouts and km over the last 30 days.</li>
      <li>Press an athlete's name to open their <b>dashboard</b>, or <b>Workouts</b> for the full list with filters.</li>
      <li>A "Coach view" bar at the top lets you switch athlete or go back to your own workouts.</li>
    </ol>
    <p class="tip">You can view and analyse their workouts, but not edit or delete them.</p>
  </article>

  <article class="usecase">
    <h3>I no longer coach an athlete</h3>
    <p>In <b>Athletes</b>, press <b>Stop coaching</b>. You lose access to their workouts at once.</p>
  </article>
</section>

<section id="athlete" class="help__section">
  <h2>I have a coach</h2>

  <article class="usecase">
    <h3>I want my coach to see my workouts</h3>
    <ol>
      <li>Your coach needs an account with "I am a coach" turned on.</li>
      <li>Find them in <b>Network</b>, open their profile and press <b>Ask to coach me</b>.</li>
      <li>Once they accept, they see all your workouts.</li>
    </ol>
  </article>

  <article class="usecase">
    <h3>A coach invited me</h3>
    <p>In <b>Network → Requests received</b>, press <b>Accept as coach</b> or <b>Reject</b>. Only accept people you know: a coach also sees workouts marked "Only me".</p>
  </article>

  <article class="usecase">
    <h3>I changed coach</h3>
    <p>In <b>Network → My coaches</b>, or on your old coach's profile, press <b>End</b>. Then ask your new coach as above. You can have more than one coach.</p>
  </article>
</section>

<section id="profile" class="help__section">
  <h2>Profile and account</h2>

  <article class="usecase">
    <h3>I want to update my details</h3>
    <p>Press your photo, <b>View profile</b>, then <b>Edit profile</b>. There you can change your display name, avatar (JPG, PNG or WebP up to 2 MB), birth date, sex, weight, height, club, country, main event and language. Press <b>Save profile</b> at the end.</p>
  </article>

  <article class="usecase">
    <h3>I want to change my email or password</h3>
    <p><b>View profile → Edit profile</b> has a section for each. Changing the password needs your current one.</p>
  </article>

  <article class="usecase">
    <h3>I want to log out or delete my account</h3>
    <ul>
      <li><b>Log out</b>: press your photo at the top right and choose <b>Log out</b>.</li>
      <li><b>Delete account</b> is at the bottom of <b>Edit profile</b>. It removes the account and every workout for good. Export the CSV first if you want to keep your data.</li>
    </ul>
  </article>
</section>

<section id="faq" class="help__section">
  <h2>FAQ</h2>
  <dl class="faq">
    <dt>How do I type times?</dt>
    <dd><kbd>1:32.4</kbd> (minutes:seconds.tenths) or <kbd>92.4</kbd> (seconds). On a phone number pad <kbd>1.32.4</kbd> also works. For long events use <kbd>1:02:15</kbd>.</dd>
    <dt>Who can see my workouts?</dt>
    <dd>You, your coaches, and whoever each workout's "Who can see it" allows. Your first workout is "Only me". Later ones use the option of the last workout you logged.</dd>
    <dt>What is the difference between deactivating and deleting?</dt>
    <dd>Deactivating hides the workout from stats and can be undone. Deleting removes it for good.</dd>
    <dt>Is the GPX file kept?</dt>
    <dd>No. Only the times and details you confirm are saved.</dd>
    <dt>Can I use it on my phone?</dt>
    <dd>Yes, the app fits any screen. In your phone browser, use "Add to Home screen" to open it like an app.</dd>
    <dt>I can't log in and it says my account is disabled.</dt>
    <dd>An administrator disabled it. Your workouts are kept. Contact the administrator to re-enable it.</dd>
  </dl>
</section>

<?php };
