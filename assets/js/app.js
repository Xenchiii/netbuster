(function () {
  "use strict";

  var $ = function (s, c) {
    return (c || document).querySelector(s);
  };
  var $$ = function (s, c) {
    return Array.prototype.slice.call((c || document).querySelectorAll(s));
  };
  var root = document.documentElement;
  var raf = window.requestAnimationFrame.bind(window);
  var reduce = !!(
    window.matchMedia &&
    window.matchMedia("(prefers-reduced-motion: reduce)").matches
  );

  var store = {
    get: function (k) {
      try {
        return localStorage.getItem(k);
      } catch (e) {
        return null;
      }
    },
    set: function (k, v) {
      try {
        localStorage.setItem(k, v);
      } catch (e) {}
    },
  };

  function mk(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text != null) n.textContent = text;
    return n;
  }

  function tween(from, to, ms, cb) {
    if (reduce) {
      cb(to);
      return;
    }
    var t0 = null;
    function step(ts) {
      if (t0 === null) t0 = ts;
      var p = Math.min(1, (ts - t0) / ms),
        e = 1 - Math.pow(1 - p, 3);
      cb(from + (to - from) * e);
      if (p < 1) raf(step);
    }
    raf(step);
  }

  function copy(text) {
    var done = function () {
      toast("Copied " + text, "success", 2200);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done, function () {
        toast("Could not copy.", "error");
      });
    } else {
      var ta = mk("textarea");
      ta.value = text;
      ta.style.position = "fixed";
      ta.style.opacity = "0";
      document.body.appendChild(ta);
      ta.select();
      try {
        document.execCommand("copy");
        done();
      } catch (e) {
        toast("Could not copy.", "error");
      }
      ta.remove();
    }
  }

  /* theme */
  var savedTheme = store.get("nb-theme");
  root.setAttribute(
    "data-theme",
    savedTheme ||
      (window.matchMedia &&
      window.matchMedia("(prefers-color-scheme: dark)").matches
        ? "dark"
        : "light"),
  );

  var themeBtn = mk("button", "theme-toggle");
  themeBtn.type = "button";
  themeBtn.setAttribute("aria-label", "Toggle dark mode");
  function paintTheme() {
    themeBtn.textContent =
      root.getAttribute("data-theme") === "dark" ? "☀" : "☾";
  }
  themeBtn.addEventListener("click", function () {
    var next = root.getAttribute("data-theme") === "dark" ? "light" : "dark";
    root.classList.add("theme-fade");
    root.setAttribute("data-theme", next);
    store.set("nb-theme", next);
    paintTheme();
    setTimeout(function () {
      root.classList.remove("theme-fade");
    }, 450);
  });
  paintTheme();
  var navHost = $("#mainNav") || $(".nav");
  if (navHost) navHost.appendChild(themeBtn);

  /* mobile nav */
  var toggle = $("#navToggle"),
    nav = $("#mainNav");
  if (toggle && nav) {
    toggle.setAttribute("aria-expanded", "false");
    toggle.setAttribute("aria-controls", "mainNav");
    toggle.addEventListener("click", function (e) {
      e.stopPropagation();
      var open = nav.classList.toggle("open");
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
    });
    document.addEventListener("click", function (e) {
      if (nav.classList.contains("open") && !nav.contains(e.target)) {
        nav.classList.remove("open");
        toggle.setAttribute("aria-expanded", "false");
      }
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && nav.classList.contains("open")) {
        nav.classList.remove("open");
        toggle.setAttribute("aria-expanded", "false");
        toggle.focus();
      }
    });
  }

  $$("nav a").forEach(function (a) {
    try {
      var u = new URL(a.href, location.href);
      if (
        u.origin === location.origin &&
        u.pathname === location.pathname &&
        !a.classList.contains("btn")
      ) {
        a.classList.add("active");
        a.setAttribute("aria-current", "page");
      }
    } catch (e) {}
  });

  /* toasts */
  var toastWrap = mk("div", "toast-wrap");
  toastWrap.setAttribute("role", "status");
  toastWrap.setAttribute("aria-live", "polite");
  document.body.appendChild(toastWrap);

  function toast(msg, type, ms) {
    type = type || "info";
    ms = ms || 4000;
    var t = mk("div", "toast toast-" + type);
    t.appendChild(
      mk(
        "span",
        "toast-icon",
        { success: "✓", error: "!", info: "i" }[type] || "i",
      ),
    );
    t.appendChild(mk("span", "toast-msg", msg));
    var x = mk("button", "toast-x", "×");
    x.type = "button";
    x.setAttribute("aria-label", "Dismiss");
    t.appendChild(x);
    var bar = mk("i", "toast-bar");
    bar.style.animationDuration = ms + "ms";
    t.appendChild(bar);
    toastWrap.appendChild(t);
    var closed = false;
    function close() {
      if (closed) return;
      closed = true;
      t.classList.add("out");
      setTimeout(function () {
        t.remove();
      }, 320);
    }
    x.addEventListener("click", close);
    setTimeout(close, ms);
    return t;
  }
  window.NB = window.NB || {};
  window.NB.toast = toast;

  window.addEventListener("offline", function () {
    toast("You are offline. Changes will not be saved.", "error", 6000);
  });
  window.addEventListener("online", function () {
    toast("Back online.", "success", 2500);
  });

  /* flash alerts */
  $$(".alert").forEach(function (a) {
    var x = mk("button", "alert-x", "×");
    x.type = "button";
    x.setAttribute("aria-label", "Dismiss");
    function kill() {
      a.classList.add("out");
      setTimeout(function () {
        a.remove();
      }, 400);
    }
    x.addEventListener("click", kill);
    a.appendChild(x);
    if (a.classList.contains("alert-success")) setTimeout(kill, 5000);
  });

  /* scroll progress, back to top, header shadow */
  var progress = mk("div", "scroll-progress");
  document.body.appendChild(progress);
  var toTop = mk("button", "to-top", "↑");
  toTop.type = "button";
  toTop.setAttribute("aria-label", "Back to top");
  document.body.appendChild(toTop);
  toTop.addEventListener("click", function () {
    window.scrollTo({ top: 0, behavior: reduce ? "auto" : "smooth" });
  });
  var siteHeader = $(".site-header"),
    ticking = false;
  function onScroll() {
    var max = root.scrollHeight - root.clientHeight,
      y = window.pageYOffset;
    progress.style.transform = "scaleX(" + (max > 0 ? y / max : 0) + ")";
    toTop.classList.toggle("show", y > 400);
    if (siteHeader) siteHeader.classList.toggle("scrolled", y > 8);
    ticking = false;
  }
  window.addEventListener(
    "scroll",
    function () {
      if (!ticking) {
        ticking = true;
        raf(onScroll);
      }
    },
    { passive: true },
  );
  onScroll();

  /* count-up numbers */
  function countUp(n) {
    var s = n.matches && n.matches(".stat") ? $("strong", n) : null;
    if (!s || s.getAttribute("data-done")) return;
    s.setAttribute("data-done", "1");
    var m = s.textContent.trim().match(/^([^\d-]*)([\d,]*\.?\d+)(.*)$/);
    if (!m || reduce) return;
    var target = parseFloat(m[2].replace(/,/g, "")),
      dec = (m[2].split(".")[1] || "").length,
      comma = m[2].indexOf(",") > -1;
    tween(0, target, 1000, function (v) {
      var t = v.toFixed(dec);
      if (comma)
        t = Number(t).toLocaleString("en-US", {
          minimumFractionDigits: dec,
          maximumFractionDigits: dec,
        });
      s.textContent = m[1] + t + m[3];
    });
  }
  $$(".hero-card .stat").forEach(countUp);

  /* reveal on scroll */
  var revealItems = $$(
    ".stats .stat,.steps .card,.grid > *,.two-col > *,.detail > *,.checkout > *,.timeline li,.section-title,.table-wrap,.form-card,.auth-box,.success-box,.filters",
  );
  if ("IntersectionObserver" in window && !reduce) {
    revealItems.forEach(function (n) {
      n.classList.add("reveal");
      var idx = n.parentNode
        ? Array.prototype.indexOf.call(n.parentNode.children, n)
        : 0;
      n.style.transitionDelay = Math.min(idx, 8) * 60 + "ms";
    });
    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (en) {
          if (!en.isIntersecting) return;
          var n = en.target;
          n.classList.add("in");
          io.unobserve(n);
          countUp(n);
          setTimeout(function () {
            n.classList.remove("reveal");
            n.style.transitionDelay = "";
          }, 1000);
        });
      },
      { threshold: 0.1, rootMargin: "0px 0px -30px 0px" },
    );
    revealItems.forEach(function (n) {
      io.observe(n);
    });
  } else {
    revealItems.forEach(function (n) {
      n.classList.add("in");
      countUp(n);
    });
  }

  /* button ripple */
  document.addEventListener("click", function (e) {
    var b = e.target.closest && e.target.closest(".btn");
    if (!b || b.disabled || reduce) return;
    var r = b.getBoundingClientRect(),
      d = Math.max(r.width, r.height),
      s = mk("span", "ripple");
    s.style.width = s.style.height = d + "px";
    s.style.left = e.clientX - r.left - d / 2 + "px";
    s.style.top = e.clientY - r.top - d / 2 + "px";
    b.appendChild(s);
    setTimeout(function () {
      s.remove();
    }, 650);
  });

  /* confirm modal */
  function confirmBox(msg, ok) {
    var back = mk("div", "modal-back"),
      box = mk("div", "modal");
    box.setAttribute("role", "dialog");
    box.setAttribute("aria-modal", "true");
    box.appendChild(mk("h3", null, "Please confirm"));
    box.appendChild(mk("p", "muted", msg));
    var row = mk("div", "modal-actions");
    var no = mk("button", "btn btn-outline btn-sm", "Cancel");
    var yes = mk("button", "btn btn-danger btn-sm", "Confirm");
    no.type = "button";
    yes.type = "button";
    row.appendChild(no);
    row.appendChild(yes);
    box.appendChild(row);
    back.appendChild(box);
    var prev = document.activeElement;
    function onKey(e) {
      if (e.key === "Escape") close();
    }
    function close() {
      back.classList.remove("show");
      document.removeEventListener("keydown", onKey);
      setTimeout(function () {
        back.remove();
        if (prev && prev.focus) prev.focus();
      }, 250);
    }
    no.addEventListener("click", close);
    yes.addEventListener("click", function () {
      close();
      ok();
    });
    back.addEventListener("click", function (e) {
      if (e.target === back) close();
    });
    document.addEventListener("keydown", onKey);
    document.body.appendChild(back);
    raf(function () {
      back.classList.add("show");
      no.focus();
    });
  }

  $$("form[data-confirm]").forEach(function (f) {
    f.addEventListener("submit", function (e) {
      if (f._confirmed) {
        f._confirmed = false;
        return;
      }
      e.preventDefault();
      e.stopImmediatePropagation();
      var sub = e.submitter;
      confirmBox(f.getAttribute("data-confirm"), function () {
        f._confirmed = true;
        if (f.requestSubmit) f.requestSubmit(sub || undefined);
        else f.submit();
      });
    });
  });

  /* form validation */
  function clearFieldError(input) {
    if (input._err) {
      input._err.remove();
      input._err = null;
    }
    input.classList.remove("invalid");
    input.removeAttribute("aria-invalid");
  }
  function clearErrors(form) {
    $$(".field-error", form).forEach(function (n) {
      n.remove();
    });
    $$("input,select,textarea", form).forEach(function (n) {
      n._err = null;
      n.classList.remove("invalid", "valid");
      n.removeAttribute("aria-invalid");
    });
  }
  function showError(input, msg) {
    if (input._err && input._err.textContent === msg) return;
    clearFieldError(input);
    input.classList.remove("valid");
    input.classList.add("invalid");
    input.setAttribute("aria-invalid", "true");
    var s = mk("span", "field-error", msg);
    s.setAttribute("role", "alert");
    var host = input.parentNode.classList.contains("pw-wrap")
      ? input.parentNode.parentNode
      : input.parentNode;
    host.appendChild(s);
    input._err = s;
    input.classList.remove("shake");
    void input.offsetWidth;
    input.classList.add("shake");
  }
  function fieldMsg(f) {
    if (
      f.type === "hidden" ||
      f.type === "submit" ||
      f.type === "button" ||
      f.type === "reset" ||
      f.disabled ||
      f.closest("[hidden]")
    )
      return "";
    if (f.type === "checkbox")
      return f.required && !f.checked
        ? "Please tick this box to continue."
        : "";
    if (f.type === "radio") return "";
    var v = f.value.trim();
    if (f.required && v === "") return "This field is required.";
    if (v === "") return "";
    if (f.minLength > 0 && v.length < f.minLength)
      return "Minimum " + f.minLength + " characters.";
    if (f.type === "email" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v))
      return "Enter a valid email address.";
    if (f.pattern) {
      try {
        if (!new RegExp("^(?:" + f.pattern + ")$").test(v))
          return f.title || "Invalid format.";
      } catch (e) {}
    }
    if (f.type === "number") {
      var n = parseFloat(v);
      if (
        isNaN(n) ||
        (f.min !== "" && n < +f.min) ||
        (f.max !== "" && n > +f.max)
      ) {
        return (
          "Enter a number between " +
          (f.min || "…") +
          " and " +
          (f.max || "…") +
          "."
        );
      }
    }
    return "";
  }
  function basicValidate(form) {
    var ok = true;
    $$("input,select,textarea", form).forEach(function (f) {
      var m = fieldMsg(f);
      if (m) {
        showError(f, m);
        ok = false;
      }
    });
    return ok;
  }
  function liveCheck(t, form) {
    var m = fieldMsg(t);
    if (
      !m &&
      form.id === "registerForm" &&
      t.id === "confirm" &&
      t.value &&
      t.value !== $("#password").value
    )
      m = "Passwords do not match.";
    if (m) {
      showError(t, m);
    } else {
      clearFieldError(t);
      t.classList.toggle(
        "valid",
        t.value.trim() !== "" && t.type !== "checkbox",
      );
    }
  }

  $$("form[data-validate]").forEach(function (form) {
    form.addEventListener("submit", function (e) {
      clearErrors(form);
      var ok = basicValidate(form);
      if (form.id === "registerForm") ok = validateRegister(form) && ok;
      if (form.id === "checkoutForm") ok = validateCheckout(form) && ok;
      if (!ok) {
        e.preventDefault();
        var first = $(".invalid", form);
        if (first) {
          first.focus();
          if (first.scrollIntoView)
            first.scrollIntoView({
              block: "center",
              behavior: reduce ? "auto" : "smooth",
            });
        }
        toast("Please fix the highlighted fields.", "error", 3200);
      }
    });
    form.addEventListener("focusout", function (e) {
      var t = e.target;
      if (!/^(INPUT|SELECT|TEXTAREA)$/.test(t.tagName)) return;
      t._touched = true;
      liveCheck(t, form);
    });
    form.addEventListener("input", function (e) {
      var t = e.target;
      if (t._touched) liveCheck(t, form);
    });
  });

  document.addEventListener("submit", function (e) {
    if (e.defaultPrevented) return;
    var f = e.target;
    if (f.target === "_blank") return;
    var b = e.submitter || $("button[type=submit]", f);
    if (!b || !b.classList.contains("btn") || b.hasAttribute("data-no-loading"))
      return;
    setTimeout(function () {
      b.classList.add("loading");
      b.disabled = true;
      setTimeout(function () {
        b.classList.remove("loading");
        b.disabled = false;
      }, 10000);
    }, 0);
  });
  window.addEventListener("pageshow", function () {
    $$(".btn.loading").forEach(function (b) {
      b.classList.remove("loading");
      b.disabled = false;
    });
  });

  /* password show/hide + caps lock hint */
  $$("input[type=password]").forEach(function (inp) {
    var wrap = mk("span", "pw-wrap");
    inp.parentNode.insertBefore(wrap, inp);
    wrap.appendChild(inp);
    var b = mk("button", "pw-toggle", "Show");
    b.type = "button";
    b.setAttribute("aria-label", "Show password");
    wrap.appendChild(b);
    b.addEventListener("click", function () {
      var show = inp.type === "password";
      inp.type = show ? "text" : "password";
      b.textContent = show ? "Hide" : "Show";
      b.setAttribute("aria-label", show ? "Hide password" : "Show password");
    });
    var warn = mk("span", "hint warn-hint", "Caps Lock is on.");
    warn.hidden = true;
    wrap.parentNode.appendChild(warn);
    inp.addEventListener("keyup", function (e) {
      warn.hidden = !(e.getModifierState && e.getModifierState("CapsLock"));
    });
    inp.addEventListener("blur", function () {
      warn.hidden = true;
    });
  });

  var pw = $("#password");
  if (pw && $("#strengthBar")) {
    pw.addEventListener("input", function () {
      var v = pw.value,
        score = 0;
      if (v.length >= 8) score++;
      if (/[A-Z]/.test(v)) score++;
      if (/\d/.test(v)) score++;
      if (/[^A-Za-z0-9]/.test(v)) score++;
      var bar = $("#strengthBar"),
        txt = $("#strengthText");
      bar.style.width = score * 25 + "%";
      bar.style.background = [
        "#dc2626",
        "#dc2626",
        "#eab308",
        "#10b981",
        "#10b981",
      ][score];
      if (txt)
        txt.textContent = v
          ? ["Very weak", "Weak", "Fair", "Good", "Strong"][score]
          : "";
    });
  }
  function validateRegister(form) {
    var ok = true,
      p = $("#password"),
      c = $("#confirm");
    if (p.value && (!/[A-Za-z]/.test(p.value) || !/\d/.test(p.value))) {
      showError(p, "Use both letters and numbers.");
      ok = false;
    }
    if (c.value && p.value !== c.value) {
      showError(c, "Passwords do not match.");
      ok = false;
    }
    return ok;
  }

  /* character counters */
  $$("textarea,input[name=notes]").forEach(function (f) {
    if (f.name === "notes" && !f.getAttribute("maxlength"))
      f.setAttribute("maxlength", "255");
    var max = parseInt(f.getAttribute("maxlength"), 10);
    if (!max) return;
    var c = mk("span", "counter");
    f.parentNode.appendChild(c);
    function upd() {
      c.textContent = f.value.length + " / " + max;
      c.classList.toggle("near", f.value.length > max * 0.9);
    }
    f.addEventListener("input", upd);
    upd();
  });

  /* quantity steppers */
  $$("input.qty[type=number]").forEach(function (inp) {
    var minus = mk("button", "step-btn", "−"),
      plus = mk("button", "step-btn", "+");
    minus.type = "button";
    plus.type = "button";
    minus.setAttribute("aria-label", "Decrease quantity");
    plus.setAttribute("aria-label", "Increase quantity");
    inp.parentNode.insertBefore(minus, inp);
    inp.parentNode.insertBefore(plus, inp.nextSibling);
    function bump(d) {
      var v = parseInt(inp.value, 10) || 0;
      var min = inp.min !== "" ? +inp.min : 1,
        max = inp.max !== "" ? +inp.max : Infinity;
      v = Math.max(min, Math.min(max, v + d));
      inp.value = v;
      inp.dispatchEvent(new Event("input", { bubbles: true }));
      inp.dispatchEvent(new Event("change", { bubbles: true }));
    }
    minus.addEventListener("click", function () {
      bump(-1);
    });
    plus.addEventListener("click", function () {
      bump(1);
    });
  });

  $$("input[name=contact_no]").forEach(function (i) {
    i.addEventListener("input", function () {
      i.value = i.value.replace(/[^\d+\-\s]/g, "");
    });
  });

  $$("[data-copy]").forEach(function (b) {
    b.addEventListener("click", function () {
      copy(b.getAttribute("data-copy"));
    });
  });
  $$("[data-print]").forEach(function (b) {
    b.addEventListener("click", function () {
      window.print();
    });
  });

  var authFirst = $(".auth-box input:not([type=hidden])");
  if (authFirst && !authFirst.value && window.innerWidth > 820)
    authFirst.focus();

  /* services: search, filter, sort, favourites, view toggle */ a;
  var grid = $("#grid");
  if (grid) {
    var cards = $$(".plan", grid),
      q = $("#q"),
      cat = $("#cat"),
      sort = $("#sort"),
      only = $("#onlyAvail");
    var count = $("#resultCount"),
      empty = $("#emptyMsg");
    var favs = [];
    try {
      favs = JSON.parse(store.get("nb-favs") || "[]");
    } catch (e) {
      favs = [];
    }
    function keyOf(c) {
      return c.dataset.title || c.dataset.name;
    }

    var qs = new URLSearchParams(location.search);
    if (qs.get("q") && !q.value) q.value = qs.get("q");

    cards.forEach(function (c) {
      var sp = +c.dataset.speed || 0;
      if (sp > 0) {
        var m = mk("div", "meter"),
          s = mk("span");
        s.style.setProperty(
          "--w",
          Math.max(6, Math.round((Math.log(sp + 1) / Math.log(1001)) * 100)) +
            "%",
        );
        m.title = sp + " Mbps";
        m.appendChild(s);
        var ref = $(".speed", c);
        if (ref && ref.parentNode)
          ref.parentNode.insertBefore(m, ref.nextSibling);
        else c.insertBefore(m, $(".btn", c) || null);
      }
      var h = $("h3", c);
      if (h) h.setAttribute("data-orig", h.textContent);
      var fb = mk("button", "fav");
      fb.type = "button";
      function paintFav() {
        var on = favs.indexOf(keyOf(c)) > -1;
        fb.textContent = on ? "♥ Saved" : "♡ Save";
        fb.classList.toggle("on", on);
        fb.setAttribute("aria-pressed", on ? "true" : "false");
      }
      paintFav();
      fb.addEventListener("click", function () {
        var k = keyOf(c),
          i = favs.indexOf(k);
        if (i > -1) favs.splice(i, 1);
        else favs.push(k);
        store.set("nb-favs", JSON.stringify(favs));
        paintFav();
        toast(
          i > -1 ? "Removed from saved plans." : "Saved for later.",
          "info",
          1800,
        );
        if (favOnly && favOnly.checked) render(true);
      });
      c.insertBefore(fb, $(".btn", c) || null);
    });

    var favOnly = null;
    var onlyHost = only.closest("label") || only;
    var favLabel = mk("label", "check");
    favOnly = mk("input");
    favOnly.type = "checkbox";
    favOnly.id = "onlyFav";
    favLabel.appendChild(favOnly);
    favLabel.appendChild(document.createTextNode(" Saved only"));
    onlyHost.parentNode.insertBefore(favLabel, onlyHost.nextSibling);

    var viewBtn = mk("button", "btn btn-outline btn-sm", "List view");
    viewBtn.type = "button";
    onlyHost.parentNode.appendChild(viewBtn);
    function setView(list) {
      grid.classList.toggle("list-view", list);
      viewBtn.textContent = list ? "Grid view" : "List view";
      store.set("nb-view", list ? "list" : "grid");
    }
    viewBtn.addEventListener("click", function () {
      setView(!grid.classList.contains("list-view"));
    });
    setView(store.get("nb-view") === "list");

    var clearBtn = mk("button", "btn btn-outline btn-sm", "Clear filters");
    clearBtn.type = "button";
    empty.appendChild(document.createElement("br"));
    empty.appendChild(clearBtn);
    clearBtn.addEventListener("click", function () {
      q.value = "";
      cat.value = "";
      sort.selectedIndex = 0;
      only.checked = false;
      favOnly.checked = false;
      render(true);
    });

    function markTitle(h, term) {
      var t = h.getAttribute("data-orig") || "",
        i = term ? t.toLowerCase().indexOf(term) : -1;
      h.textContent = "";
      if (i < 0) {
        h.textContent = t;
        return;
      }
      h.appendChild(document.createTextNode(t.slice(0, i)));
      h.appendChild(mk("mark", null, t.slice(i, i + term.length)));
      h.appendChild(document.createTextNode(t.slice(i + term.length)));
    }

    function apply() {
      var term = q.value.trim().toLowerCase(),
        shown = 0;
      cards.forEach(function (c) {
        var match =
          (!term || c.dataset.name.indexOf(term) > -1) &&
          (!cat.value || c.dataset.cat === cat.value) &&
          (!only.checked || c.dataset.avail === "1") &&
          (!favOnly.checked || favs.indexOf(keyOf(c)) > -1);
        c.hidden = !match;
        if (match) shown++;
        var h = $("h3", c);
        if (h && h.getAttribute("data-orig")) markTitle(h, term);
      });
      var by = sort.value,
        arr = cards.slice();
      arr
        .sort(function (a, b) {
          if (by === "price-asc") return a.dataset.price - b.dataset.price;
          if (by === "price-desc") return b.dataset.price - a.dataset.price;
          if (by === "speed-desc") return b.dataset.speed - a.dataset.speed;
          if (by === "name")
            return a.dataset.title.localeCompare(b.dataset.title);
          return a.dataset.order - b.dataset.order;
        })
        .forEach(function (c) {
          grid.appendChild(c);
        });
      count.textContent =
        shown + " service" + (shown === 1 ? "" : "s") + " found";
      count.classList.remove("bump");
      void count.offsetWidth;
      count.classList.add("bump");
      empty.hidden = shown !== 0;
    }

    function render(animate) {
      if (!animate || reduce) {
        apply();
        return;
      }
      var before = cards.filter(function (c) {
        return !c.hidden;
      });
      var first = before.map(function (c) {
        return c.getBoundingClientRect();
      });
      apply();
      cards
        .filter(function (c) {
          return !c.hidden;
        })
        .forEach(function (c) {
          var i = before.indexOf(c);
          if (i < 0) {
            c.animate(
              [
                { opacity: 0, transform: "scale(.95)" },
                { opacity: 1, transform: "none" },
              ],
              { duration: 280, easing: "ease-out" },
            );
            return;
          }
          var n = c.getBoundingClientRect(),
            dx = first[i].left - n.left,
            dy = first[i].top - n.top;
          if (dx || dy)
            c.animate(
              [
                { transform: "translate(" + dx + "px," + dy + "px)" },
                { transform: "none" },
              ],
              { duration: 340, easing: "cubic-bezier(.2,.8,.2,1)" },
            );
        });
    }

    q.addEventListener("input", function () {
      render(true);
    });
    [cat, sort, only, favOnly].forEach(function (n) {
      n.addEventListener("change", function () {
        render(true);
      });
    });
    render(false);

    document.addEventListener("keydown", function (e) {
      if (
        e.key === "/" &&
        !/^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName)
      ) {
        e.preventDefault();
        q.focus();
      }
      if (e.key === "Escape" && document.activeElement === q && q.value) {
        q.value = "";
        render(true);
      }
    });
  }

  /* admin tables */
  $$("table.sortable").forEach(function (t) {
    var heads = $$("th", t),
      body = t.tBodies[0];
    var host = t.closest(".table-wrap") || t;
    var tools = mk("div", "table-tools no-print");
    var search = mk("input");
    search.type = "search";
    search.placeholder = "Filter rows…";
    search.setAttribute("aria-label", "Filter table rows");
    var info = mk("span", "muted");
    var exp = mk("button", "btn btn-outline btn-sm", "Export CSV");
    exp.type = "button";
    tools.appendChild(search);
    tools.appendChild(info);
    tools.appendChild(exp);
    host.parentNode.insertBefore(tools, host);

    function dataRows() {
      return $$("tr", body).filter(function (r) {
        return r.cells.length > 1;
      });
    }
    function filterRows() {
      var term = search.value.trim().toLowerCase(),
        rows = dataRows(),
        shown = 0;
      rows.forEach(function (r) {
        var hide =
          term !== "" && r.textContent.toLowerCase().indexOf(term) === -1;
        r.hidden = hide;
        if (!hide) shown++;
      });
      info.textContent = shown + " of " + rows.length + " rows";
    }
    search.addEventListener("input", filterRows);
    filterRows();

    $$("th[data-sort]", t).forEach(function (th) {
      var idx = heads.indexOf(th),
        dir = 1;
      th.tabIndex = 0;
      th.setAttribute("role", "button");
      function go() {
        var rows = $$("tr", body).filter(function (r) {
          return r.cells.length > idx;
        });
        rows
          .sort(function (a, b) {
            var x = a.cells[idx],
              y = b.cells[idx];
            if (th.dataset.sort === "num")
              return (
                dir *
                ((parseFloat(x.dataset.v) || 0) -
                  (parseFloat(y.dataset.v) || 0))
              );
            return (
              dir * x.textContent.trim().localeCompare(y.textContent.trim())
            );
          })
          .forEach(function (r) {
            body.appendChild(r);
          });
        heads.forEach(function (h) {
          h.removeAttribute("data-dir");
        });
        th.setAttribute("data-dir", dir === 1 ? "asc" : "desc");
        dir = -dir;
        if (!reduce) {
          rows.slice(0, 60).forEach(function (r, i) {
            r.animate(
              [
                { opacity: 0.25, transform: "translateY(6px)" },
                { opacity: 1, transform: "none" },
              ],
              {
                duration: 260,
                delay: i * 12,
                easing: "ease-out",
                fill: "backwards",
              },
            );
          });
        }
      }
      th.addEventListener("click", go);
      th.addEventListener("keydown", function (e) {
        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault();
          go();
        }
      });
    });

    exp.addEventListener("click", function () {
      var keep = [];
      heads.forEach(function (h, i) {
        var tx = h.textContent.trim();
        if (tx && !/^actions?$/i.test(tx)) keep.push(i);
      });
      if (!keep.length) return;
      function cell(v) {
        v = v.replace(/\s+/g, " ").trim();
        if (/^[=+@]/.test(v) || /^-[^\d.]/.test(v)) v = "'" + v;
        return '"' + v.replace(/"/g, '""') + '"';
      }
      var lines = [
        keep
          .map(function (i) {
            return cell(heads[i].textContent);
          })
          .join(","),
      ];
      var maxIdx = keep[keep.length - 1];
      dataRows().forEach(function (r) {
        if (r.hidden || r.cells.length <= maxIdx) return;
        lines.push(
          keep
            .map(function (i) {
              return cell(r.cells[i].textContent);
            })
            .join(","),
        );
      });
      var blob = new Blob(["\ufeff" + lines.join("\r\n")], {
        type: "text/csv;charset=utf-8",
      });
      var a = document.createElement("a");
      a.href = URL.createObjectURL(blob);
      a.download =
        (document.title
          .split(/[|–-]/)[0]
          .trim()
          .toLowerCase()
          .replace(/\s+/g, "-") || "table") + ".csv";
      document.body.appendChild(a);
      a.click();
      a.remove();
      setTimeout(function () {
        URL.revokeObjectURL(a.href);
      }, 1000);
      toast("CSV exported.", "success", 2500);
    });
  });

  /* checkout */
  function cardBrand(d) {
    if (/^4/.test(d)) return "Visa";
    if (/^(5[1-5]|2[2-7])/.test(d)) return "Mastercard";
    if (/^3[47]/.test(d)) return "Amex";
    if (/^35/.test(d)) return "JCB";
    if (/^62/.test(d)) return "UnionPay";
    return "";
  }

  var co = $("#checkoutForm");
  if (co) {
    var subtotal = parseFloat(co.dataset.subtotal) || 0;
    var fmt = function (n) {
      return (
        "₱" +
        n.toLocaleString("en-PH", {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        })
      );
    };
    var shownFee = 0,
      shownTotal = subtotal;
    function updateTotals(instant) {
      var pri = $("input[name=setup_type]:checked", co).value === "Priority";
      var fee = pri ? window.PRIORITY_FEE || 0 : 0;
      var f = $("#feeOut"),
        t = $("#totalOut");
      if (instant === true) {
        f.textContent = fmt(fee);
        t.textContent = fmt(subtotal + fee);
      } else {
        tween(shownFee, fee, 350, function (v) {
          f.textContent = fmt(v);
        });
        tween(shownTotal, subtotal + fee, 350, function (v) {
          t.textContent = fmt(v);
        });
      }
      shownFee = fee;
      shownTotal = subtotal + fee;
      t.classList.remove("bump");
      void t.offsetWidth;
      t.classList.add("bump");
    }
    $$("input[name=setup_type]", co).forEach(function (r) {
      r.addEventListener("change", function () {
        updateTotals();
      });
    });
    updateTotals(true);

    var pm = $("#payMethod");
    function updatePanes() {
      $$(".pay-pane", co).forEach(function (p) {
        p.hidden = p.dataset.for.split(" ").indexOf(pm.value) === -1;
      });
    }
    pm.addEventListener("change", updatePanes);
    updatePanes();

    var cn = $("#cardNumber");
    cn.setAttribute("inputmode", "numeric");
    cn.setAttribute("autocomplete", "cc-number");
    var brand = mk("span", "card-brand");
    cn.parentNode.insertBefore(brand, cn.nextSibling);
    cn.addEventListener("input", function () {
      var d = cn.value.replace(/\D/g, "").slice(0, 19);
      cn.value = d.replace(/(.{4})/g, "$1 ").trim();
      brand.textContent = cardBrand(d);
      brand.classList.toggle("on", !!brand.textContent);
    });

    var exp = $("#cardExpiry") || $("input[name=card_expiry]", co);
    if (exp) {
      exp.setAttribute("inputmode", "numeric");
      exp.setAttribute("autocomplete", "cc-exp");
      exp.setAttribute(
        "placeholder",
        exp.getAttribute("placeholder") || "MM/YY",
      );
      exp.addEventListener("input", function () {
        var d = exp.value.replace(/\D/g, "").slice(0, 4);
        exp.value = d.length > 2 ? d.slice(0, 2) + "/" + d.slice(2) : d;
      });
    }
    var cvv = $("#cardCvv") || $("input[name=card_cvv]", co);
    if (cvv) {
      cvv.setAttribute("inputmode", "numeric");
      cvv.setAttribute("autocomplete", "cc-csc");
      cvv.addEventListener("input", function () {
        cvv.value = cvv.value.replace(/\D/g, "").slice(0, 4);
      });
    }
    var wallet = $("input[name=ewallet_no]", co);
    if (wallet) {
      wallet.setAttribute("inputmode", "numeric");
      wallet.addEventListener("input", function () {
        wallet.value = wallet.value.replace(/\D/g, "").slice(0, 11);
      });
    }
  }

  function luhn(num) {
    num = num.replace(/\D/g, "");
    if (num.length < 13 || num.length > 19) return false;
    var sum = 0,
      alt = false;
    for (var i = num.length - 1; i >= 0; i--) {
      var n = parseInt(num[i], 10);
      if (alt) {
        n *= 2;
        if (n > 9) n -= 9;
      }
      sum += n;
      alt = !alt;
    }
    return sum % 10 === 0;
  }

  function validateCheckout(form) {
    var ok = true,
      m = $("#payMethod").value;
    if (m === "Card") {
      var name = $("input[name=card_name]", form),
        num = $("#cardNumber");
      if (name.value.trim().length < 3) {
        showError(name, "Enter the name on the card.");
        ok = false;
      }
      if (!luhn(num.value)) {
        showError(num, "Card number is invalid.");
        ok = false;
      }
      var exp = $("#cardExpiry") || $("input[name=card_expiry]", form);
      if (exp && exp.value) {
        var mm = parseInt(exp.value.slice(0, 2), 10),
          yy = parseInt(exp.value.slice(3, 5), 10),
          now = new Date();
        var past =
          yy < now.getFullYear() % 100 ||
          (yy === now.getFullYear() % 100 && mm < now.getMonth() + 1);
        if (!/^\d{2}\/\d{2}$/.test(exp.value) || mm < 1 || mm > 12 || past) {
          showError(exp, "Enter a valid, unexpired date (MM/YY).");
          ok = false;
        }
      }
      var cvv = $("#cardCvv") || $("input[name=card_cvv]", form);
      if (cvv && cvv.value && !/^\d{3,4}$/.test(cvv.value)) {
        showError(cvv, "Enter the 3 or 4 digit security code.");
        ok = false;
      }
    } else if (m === "GCash" || m === "Maya") {
      var w = $("input[name=ewallet_no]", form);
      if (!/^09\d{9}$/.test(w.value.trim())) {
        showError(w, "Enter a valid 11-digit mobile number (09XXXXXXXXX).");
        ok = false;
      }
    }
    return ok;
  }

  /* order success */ a;
  var orderNo = $(".orderno");
  if (orderNo) {
    orderNo.title = "Click to copy";
    orderNo.style.cursor = "pointer";
    orderNo.addEventListener("click", function () {
      copy(orderNo.textContent.trim());
    });
  }
  if ($(".check-big") && !reduce) {
    var colors = [
      "#f97316",
      "#fb923c",
      "#10b981",
      "#0ea5e9",
      "#eab308",
      "#e11d48",
    ];
    var wrap = mk("div", "confetti");
    wrap.setAttribute("aria-hidden", "true");
    for (var i = 0; i < 48; i++) {
      var p = mk("i");
      p.style.left = Math.random() * 100 + "%";
      p.style.background = colors[i % colors.length];
      p.style.setProperty("--dx", Math.random() * 200 - 100 + "px");
      p.style.setProperty("--r", Math.random() * 720 - 360 + "deg");
      p.style.animationDelay = Math.random() * 0.4 + "s";
      p.style.animationDuration = 1.8 + Math.random() * 1.4 + "s";
      wrap.appendChild(p);
    }
    document.body.appendChild(wrap);
    setTimeout(function () {
      wrap.remove();
    }, 4200);
  }
})();
