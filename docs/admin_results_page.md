# How to change the look of the results page

The page that shows the results of an election can have a **logo**, a line of **header text**, a **footer**, and a short **celebration** such as confetti. These settings only change what people see, so unlike the rest of an election's settings they can be changed at any time, including while the election is running and after the results are published. Every change is written to the [audit log](reference_security_and_audit.md).

You can set them in two places:

- On the **Display** tab when you [create or edit an election](admin_create_election.md) (before it starts).
- On the **Results page** form, opened with the **Results page** button on the [election overview](admin_election_overview.md). This works at any time.

## Logo, header and footer

- **Header text**: plain text shown above the results, up to 200 characters.
- **Header logo**: a PNG, JPEG, GIF or WebP picture, up to 2 MB and 4000 by 4000 pixels. It is shown above the results, up to 120 pixels high. Uploading a new picture replaces the old one, and **Remove the logo** takes it away. SVG pictures are not accepted. The picture is read and written again when it is uploaded, which removes anything hidden in the file.
- **Logo description**: what the logo shows, for people who cannot see it. It is needed whenever there is a logo.
- **Footer text**: plain text shown below the results, up to 1000 characters.

The text is shown exactly as typed: it is plain text, so HTML and links are not interpreted, and line breaks are kept. The same header and footer are shown on the results page and on the election summary page. The logo is shown to everyone who can see the results, including people using a personal link, and only once the results are published. Administrators can see it earlier.

## Celebration

Tick **Display celebration effects on the Election results page**, then choose:

- **Celebration effect**: **Confetti**, **Fireworks**, **Balloons**, **Golden sparkle**, **Ticker tape and spotlight**, or **Surprise me**, which gives each election one of the effects (always the same one for the same election). **Preview** shows the effect over the form.
- **Celebration lasts (days)**: how many days after the results are published the celebration is shown, from 1 to 30 (5 by default).

The celebration is shown every time the results page is opened, from the moment the results are published until the end of those days. After that the page is shown without it. It stops by itself after a few seconds, with the **Skip the celebration** button or with the Escape key, and it never gets in the way of clicking on the page.

People whose device asks for reduced motion do not see the effect; they see a short congratulations message instead. The effects are drawn by a small script that comes with the extension, so they work the same on WordPress, Drupal and Joomla and need nothing from outside the site.
