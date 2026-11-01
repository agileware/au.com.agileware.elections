/**
 * Celebration effects for the election results page, drawn on a canvas over the page with no library, so the
 * same file works on WordPress, Drupal and Joomla and under a Content Security Policy that blocks outside
 * scripts. Also used by the Preview button on the election forms.
 *
 * Nothing is shown to people whose device asks for reduced motion. The effect ends by itself after a few
 * seconds, on Escape, or with the Skip button, never blocks clicks on the page, and pauses while the tab is hidden.
 */
(function ($, CRM) {
  'use strict';

  var settings = CRM.vars.electionsCelebrate || {};
  var ts = CRM.ts('au.com.agileware.elections');
  var DURATION = 6500;
  var COLOURS = ['#f94144', '#f3722c', '#f8961e', '#f9c74f', '#90be6d', '#43aa8b', '#4d908e', '#577590', '#9b5de5', '#f15bb5'];
  var GOLD = ['#fff3b0', '#ffe066', '#ffd23f', '#f4b400', '#e09f00', '#fff'];
  var running = null;

  function rand(min, max) {
    return min + Math.random() * (max - min);
  }

  function pick(list) {
    return list[Math.floor(Math.random() * list.length)];
  }

  function prefersReducedMotion() {
    return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
  }

  // How many particles: fewer on small screens so phones stay smooth.
  function scale(w, h) {
    return Math.max(0.45, Math.min(1, (w * h) / (1280 * 720)));
  }

  // ---- The effects. Each returns {draw(ctx, w, h, t, dt)} and keeps its own particles.

  function confetti(w, h) {
    var parts = [];
    var n = Math.round(130 * scale(w, h));
    [0, 1].forEach(function (side) {
      for (var i = 0; i < n; i++) {
        var theta = rand(0.9, 1.35);
        var speed = rand(11, 25) * Math.max(0.7, h / 900);
        parts.push({
          x: side ? w + 10 : -10, y: h * 0.85,
          vx: (side ? -1 : 1) * Math.cos(theta) * speed,
          vy: -Math.sin(theta) * speed,
          size: rand(6, 12), rot: rand(0, 6.28), vr: rand(-0.3, 0.3), colour: pick(COLOURS), delay: rand(0, 600)
        });
      }
    });
    return {
      draw: function (ctx, w2, h2, t, dt) {
        parts.forEach(function (p) {
          if (t < p.delay) {
            return;
          }
          p.vy += 0.34 * dt;
          p.vx *= 0.992;
          p.vy *= 0.992;
          p.x += p.vx * dt;
          p.y += p.vy * dt;
          p.rot += p.vr * dt;
          ctx.save();
          ctx.globalAlpha = Math.max(0, Math.min(1, (DURATION - t) / 1500));
          ctx.translate(p.x, p.y);
          ctx.rotate(p.rot);
          ctx.scale(1, Math.abs(Math.cos(p.rot * 1.7)) * 0.9 + 0.1);
          ctx.fillStyle = p.colour;
          ctx.fillRect(-p.size / 2, -p.size / 4, p.size, p.size / 2);
          ctx.restore();
        });
      }
    };
  }

  function fireworks(w, h) {
    var rockets = [];
    var sparks = [];
    var next = 0;
    function launch() {
      rockets.push({x: rand(w * 0.15, w * 0.85), y: h, vy: -rand(11, 15) * Math.max(0.7, h / 900), target: rand(h * 0.15, h * 0.45), colour: pick(COLOURS)});
    }
    return {
      draw: function (ctx, w2, h2, t, dt) {
        if (t > next && t < DURATION - 2000) {
          launch();
          next = t + rand(250, 650);
        }
        rockets = rockets.filter(function (r) {
          r.y += r.vy * dt;
          r.vy *= 0.985;
          ctx.fillStyle = '#fff';
          ctx.fillRect(r.x - 1.5, r.y, 3, 10);
          if (r.y <= r.target || r.vy > -2) {
            var count = Math.round(70 * scale(w2, h2)) + 20;
            for (var i = 0; i < count; i++) {
              var a = rand(0, 6.283);
              var s = rand(1.5, 6.5);
              sparks.push({x: r.x, y: r.y, vx: Math.cos(a) * s, vy: Math.sin(a) * s, life: 1, colour: Math.random() < 0.25 ? '#fff' : r.colour});
            }
            return false;
          }
          return true;
        });
        sparks = sparks.filter(function (p) {
          p.vy += 0.06 * dt;
          p.vx *= 0.985;
          p.vy *= 0.985;
          p.x += p.vx * dt;
          p.y += p.vy * dt;
          p.life -= 0.012 * dt;
          if (p.life <= 0) {
            return false;
          }
          ctx.globalAlpha = p.life;
          ctx.fillStyle = p.colour;
          ctx.beginPath();
          ctx.arc(p.x, p.y, 2.2, 0, 6.283);
          ctx.fill();
          return true;
        });
        ctx.globalAlpha = 1;
      }
    };
  }

  function balloons(w, h) {
    var parts = [];
    var n = Math.round(34 * scale(w, h));
    for (var i = 0; i < n; i++) {
      parts.push({x: rand(0.03, 0.97) * w, y: h + rand(60, 260), r: rand(20, 36), vy: -rand(1.6, 3.2), phase: rand(0, 6.28), sway: rand(0.4, 1.2), colour: pick(COLOURS), delay: rand(0, 3200)});
    }
    return {
      draw: function (ctx, w2, h2, t, dt) {
        parts.forEach(function (p) {
          if (t < p.delay) {
            return;
          }
          p.y += p.vy * dt;
          var x = p.x + Math.sin(t / 600 + p.phase) * 18 * p.sway;
          ctx.globalAlpha = Math.max(0, Math.min(1, (DURATION - t) / 1200));
          ctx.strokeStyle = 'rgba(80,80,80,0.6)';
          ctx.lineWidth = 1;
          ctx.beginPath();
          ctx.moveTo(x, p.y + p.r * 1.25);
          ctx.quadraticCurveTo(x + 8 * Math.sin(t / 400 + p.phase), p.y + p.r * 2.2, x, p.y + p.r * 3);
          ctx.stroke();
          ctx.fillStyle = p.colour;
          ctx.beginPath();
          ctx.ellipse(x, p.y, p.r * 0.82, p.r, 0, 0, 6.283);
          ctx.fill();
          ctx.fillStyle = 'rgba(255,255,255,0.35)';
          ctx.beginPath();
          ctx.ellipse(x - p.r * 0.3, p.y - p.r * 0.35, p.r * 0.2, p.r * 0.32, -0.5, 0, 6.283);
          ctx.fill();
          ctx.fillStyle = p.colour;
          ctx.beginPath();
          ctx.moveTo(x, p.y + p.r);
          ctx.lineTo(x - 4, p.y + p.r * 1.25);
          ctx.lineTo(x + 4, p.y + p.r * 1.25);
          ctx.fill();
        });
        ctx.globalAlpha = 1;
      }
    };
  }

  function star(ctx, x, y, size, rot) {
    ctx.save();
    ctx.translate(x, y);
    ctx.rotate(rot);
    ctx.beginPath();
    for (var i = 0; i < 8; i++) {
      var r = i % 2 ? size * 0.22 : size;
      var a = (i * Math.PI) / 4;
      ctx.lineTo(Math.cos(a) * r, Math.sin(a) * r);
    }
    ctx.closePath();
    ctx.fill();
    ctx.restore();
  }

  function sparkle(w, h) {
    var parts = [];
    var n = Math.round(150 * scale(w, h));
    for (var i = 0; i < n; i++) {
      parts.push({x: rand(0, w), y: -rand(10, h * 0.6), vy: rand(1.2, 3.6), size: rand(4, 11), phase: rand(0, 6.28), colour: pick(GOLD), delay: rand(0, 2800)});
    }
    return {
      draw: function (ctx, w2, h2, t, dt) {
        var glow = ctx.createLinearGradient(0, 0, 0, h2 * 0.5);
        glow.addColorStop(0, 'rgba(255,214,64,' + (0.28 * Math.max(0, Math.min(1, (DURATION - t) / 1500))) + ')');
        glow.addColorStop(1, 'rgba(255,214,64,0)');
        ctx.fillStyle = glow;
        ctx.fillRect(0, 0, w2, h2 * 0.5);
        parts.forEach(function (p) {
          if (t < p.delay) {
            return;
          }
          p.y += p.vy * dt;
          var twinkle = 0.55 + 0.45 * Math.sin(t / 150 + p.phase);
          ctx.globalAlpha = twinkle * Math.max(0, Math.min(1, (DURATION - t) / 1200));
          ctx.fillStyle = p.colour;
          star(ctx, p.x + Math.sin(t / 700 + p.phase) * 12, p.y, p.size * (0.7 + twinkle * 0.5), t / 500 + p.phase);
        });
        ctx.globalAlpha = 1;
      }
    };
  }

  function ticker(w, h) {
    var ribbons = [];
    var n = Math.round(42 * scale(w, h));
    for (var i = 0; i < n; i++) {
      ribbons.push({x: rand(0, w), y: -rand(20, h * 0.5), vy: rand(2.4, 5), len: rand(60, 140), amp: rand(8, 22), freq: rand(0.02, 0.05), phase: rand(0, 6.28), colour: pick(COLOURS), delay: rand(0, 2400), width: rand(3, 6)});
    }
    return {
      draw: function (ctx, w2, h2, t, dt) {
        var fade = Math.max(0, Math.min(1, (DURATION - t) / 1500)) * Math.min(1, t / 500);
        // Two spotlights sweeping across the page.
        [0, 1].forEach(function (i) {
          var cx = w2 * (0.5 + 0.38 * Math.sin(t / 1100 + i * 2.4));
          var g = ctx.createRadialGradient(cx, h2 * 0.55, 10, cx, h2 * 0.55, Math.max(w2, h2) * 0.45);
          g.addColorStop(0, 'rgba(255,255,220,' + 0.26 * fade + ')');
          g.addColorStop(1, 'rgba(255,255,220,0)');
          ctx.fillStyle = g;
          ctx.fillRect(0, 0, w2, h2);
        });
        ribbons.forEach(function (r) {
          if (t < r.delay) {
            return;
          }
          r.y += r.vy * dt;
          ctx.globalAlpha = Math.max(0, Math.min(1, (DURATION - t) / 1200));
          ctx.strokeStyle = r.colour;
          ctx.lineWidth = r.width;
          ctx.lineCap = 'round';
          ctx.beginPath();
          for (var s = 0; s <= r.len; s += 6) {
            var x = r.x + Math.sin((r.y + s) * r.freq + r.phase + t / 400) * r.amp;
            if (s === 0) {
              ctx.moveTo(x, r.y + s);
            }
            else {
              ctx.lineTo(x, r.y + s);
            }
          }
          ctx.stroke();
        });
        ctx.globalAlpha = 1;
      }
    };
  }

  var EFFECTS = {confetti: confetti, fireworks: fireworks, balloons: balloons, sparkle: sparkle, ticker: ticker};

  function stop() {
    if (!running) {
      return;
    }
    running.stopped = true;
    cancelAnimationFrame(running.frame);
    $(document).off('.electionCelebrate');
    running.$canvas.remove();
    running.$skip.remove();
    running = null;
  }

  /**
   * Show an effect over the page.
   *
   * @param {string} name confetti, fireworks, balloons, sparkle or ticker
   * @returns {boolean} false when nothing was shown
   */
  function play(name) {
    stop();
    if (!EFFECTS[name]) {
      return false;
    }
    var canvas = document.createElement('canvas');
    if (!canvas.getContext) {
      return false;
    }
    var dpr = Math.min(window.devicePixelRatio || 1, 2);
    var w = window.innerWidth;
    var h = window.innerHeight;
    canvas.width = Math.round(w * dpr);
    canvas.height = Math.round(h * dpr);
    canvas.className = 'election-celebration-canvas';
    canvas.setAttribute('aria-hidden', 'true');
    canvas.style.cssText = 'position:fixed;left:0;top:0;width:100vw;height:100vh;z-index:99998;pointer-events:none;';
    var ctx = canvas.getContext('2d');
    ctx.scale(dpr, dpr);
    var $skip = $('<button type="button" class="election-celebration-skip"></button>').text(settings.skip || ts('Skip the celebration'));
    $skip.css({position: 'fixed', right: '1rem', bottom: '1rem', zIndex: 99999});
    $('body').append(canvas, $skip);
    var effect = EFFECTS[name](w, h);
    running = {$canvas: $(canvas), $skip: $skip, stopped: false, frame: 0};
    $skip.on('click', stop);
    $(document).on('keydown.electionCelebrate', function (e) {
      if (e.key === 'Escape') {
        stop();
      }
    });
    var elapsed = 0;
    var last = null;
    var current = running;
    function frame(now) {
      if (current.stopped) {
        return;
      }
      if (document.hidden) {
        last = null;
        current.frame = requestAnimationFrame(frame);
        return;
      }
      var dt = last === null ? 1 : Math.min((now - last) / 16.667, 3);
      last = now;
      elapsed += dt * 16.667;
      ctx.clearRect(0, 0, w, h);
      effect.draw(ctx, w, h, elapsed, dt);
      if (elapsed >= DURATION) {
        stop();
        return;
      }
      current.frame = requestAnimationFrame(frame);
    }
    current.frame = requestAnimationFrame(frame);
    return true;
  }

  window.ElectionCelebrate = {play: play, stop: stop, effects: Object.keys(EFFECTS)};

  $(function () {
    // The election form: options only matter when the celebration is switched on, and Preview shows the effect.
    var $enabled = $('input[type=checkbox][name=celebration_enabled]');
    if ($enabled.length) {
      var toggle = function () {
        $('.election-celebration-option').toggle($enabled.is(':checked'));
      };
      $enabled.on('change', toggle);
      toggle();
      $('#election-celebration-preview').on('click', function (e) {
        e.preventDefault();
        var name = $('#celebration_effect').val();
        if (name === 'surprise') {
          name = pick(Object.keys(EFFECTS));
        }
        if (prefersReducedMotion()) {
          CRM.alert(ts('Your device is set to reduce motion, so the celebration is not shown.'), ts('Preview'), 'info');
          return;
        }
        play(name);
      });
    }

    // The results page: celebrate when it is due, unless the device asks for reduced motion.
    if (settings.auto && settings.effect) {
      if (prefersReducedMotion()) {
        $('.crm-election-view-block').first().prepend($('<p class="election-celebration-banner" role="status"></p>').text(ts('Congratulations to everyone elected!')));
      }
      else {
        play(settings.effect);
      }
    }
  });
}(CRM.$, CRM));
