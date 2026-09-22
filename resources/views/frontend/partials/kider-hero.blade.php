@php
    $heroImages = [
        asset('assets/frontend/img/hero section/ChatGPT Image Sep 16, 2026, 10_31_37 AM (1).png'),
        asset('assets/frontend/img/hero section/ChatGPT Image Sep 16, 2026, 10_31_39 AM (2).png'),
        asset('assets/frontend/img/hero section/ChatGPT Image Sep 16, 2026, 10_31_40 AM (3).png'),
        asset('assets/frontend/img/hero section/ChatGPT Image Sep 16, 2026, 10_31_42 AM (5).png'),
        asset('assets/frontend/img/hero section/ChatGPT Image Sep 16, 2026, 10_31_42 AM (6).png'),
        asset('assets/frontend/img/hero section/ChatGPT Image Sep 16, 2026, 10_31_43 AM (7).png'),
        asset('assets/frontend/img/hero section/ChatGPT Image Sep 16, 2026, 10_31_44 AM (8).png'),
    ];
@endphp

<style>
    .bb-hero {
        position: relative;
        min-height: 720px;
        overflow: hidden;
        background: #fff;
        isolation: isolate;
        margin-top: 0;
    }

    .bb-hero::before {
        display: none;
    }

    .bb-hero-content {
        position: relative;
        z-index: 4;
        max-width: 850px;
        margin: 0 auto;
        padding: 4.15rem 1rem 0;
        text-align: center;
        pointer-events: none;
    }

    .bb-hero h1 {
        color: #15110D;
        font-size: clamp(3.1rem, 7vw, 6.25rem);
        line-height: .95;
        font-weight: 400;
        letter-spacing: 0;
        margin-bottom: 1rem;
    }

    .bb-hero h1 em {
        font-family: "Porcelain", cursive;
        font-style: italic;
        font-weight: 400;
    }

    .bb-hero-brown {
        color: #5E442B;
    }

    .bb-hero p {
        color: #6C6258;
        max-width: 560px;
        margin: 0 auto 1.45rem;
        font-weight: 400;
    }

    .bb-hero-actions {
        display: flex;
        justify-content: center;
        gap: .75rem;
        flex-wrap: wrap;
        pointer-events: auto;
    }

    .bb-hero-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 48px;
        border-radius: 50rem;
        padding: .85rem 1.35rem;
        font-weight: 400;
        text-decoration: none;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .bb-hero-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(94, 68, 43, .18);
    }

    .bb-hero-btn-primary {
        background: #5E442B;
        color: #fff;
        border: 2px solid #5E442B;
    }

    .bb-hero-btn-primary:hover {
        color: #fff;
    }

    .bb-hero-btn-outline {
        background: #FFFFFF;
        color: #5E442B;
        border: 2px solid #5E442B;
    }

    .bb-physics-wall {
        position: absolute;
        inset: 0;
        z-index: 2;
        overflow: hidden;
        background: #FFFFFF;
    }

    .bb-physics-wall canvas {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        display: block;
        touch-action: none;
        cursor: grab;
    }

    .bb-physics-wall canvas:active {
        cursor: grabbing;
    }

    @media (max-width: 991.98px) {
        .bb-hero {
            min-height: 660px;
        }

        .bb-hero-content {
            padding-top: 3.8rem;
        }
    }
</style>

<section class="bb-hero">
    <div
        class="bb-physics-wall"
        data-images='@json($heroImages)'
        data-sticker-count="{{ count($heroImages) }}"
        data-sticker-size="160"
        data-size-randomness="0.22"
        data-gravity-strength="1"
        data-restitution="0.5"
        data-friction="0.25"
        data-throw-power="1"
        data-border-radius="18"
        aria-hidden="true"
    >
        <canvas aria-label="Physics Sticker Wall"></canvas>
    </div>

    <div class="bb-hero-content">
        <h1><span class="bb-hero-brown">Tiny treasures.</span><br><em>Big smiles</em> <span class="bb-hero-brown">for kids.</span></h1>
        <p>Discover dolls, bags, stationery, playful accessories, and sweet surprises made for cheerful little moments.</p>
        <div class="bb-hero-actions">
            <a href="{{ route('store.products') }}" class="bb-hero-btn bb-hero-btn-primary">Shop Now</a>
            <a href="{{ route('store.products') }}" class="bb-hero-btn bb-hero-btn-outline">View Categories</a>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/matter-js@0.20.0/build/matter.min.js"></script>
<script>
    (() => {
        const initPhysicsStickerWall = (root) => {
            if (!window.Matter || root.dataset.ready === "true") return;
            root.dataset.ready = "true";

            const canvas = root.querySelector("canvas");
            const ctx = canvas.getContext("2d");
            const images = JSON.parse(root.dataset.images || "[]").map((src) => ({ src }));
            const stickerCount = Number(root.dataset.stickerCount || 8);
            const stickerSize = Number(root.dataset.stickerSize || 160);
            const sizeRandomness = Number(root.dataset.sizeRandomness || .22);
            const gravityStrength = Number(root.dataset.gravityStrength || 1);
            const restitution = Number(root.dataset.restitution || .5);
            const friction = Number(root.dataset.friction || .25);
            const throwPower = Number(root.dataset.throwPower || 1);
            const borderRadius = Number(root.dataset.borderRadius || 0);

            const {
                Engine,
                Runner,
                Bodies,
                Composite,
                Body,
                Query,
                Sleeping,
                Events,
            } = Matter;

            const engine = Engine.create({
                enableSleeping: true,
                positionIterations: 11,
                velocityIterations: 9,
                constraintIterations: 2,
            });
            engine.gravity.x = 0;
            engine.gravity.y = gravityStrength;

            const runner = Runner.create();
            const boundaries = [];
            const stickers = [];
            const loadedImages = [];
            const drag = { body: null, points: [] };
            const size = { width: 300, height: 300, dpr: 1 };

            const randomInRange = (min, max) => min + Math.random() * (max - min);

            const roundedRectPath = (x, y, w, h, radius) => {
                const r = Math.max(0, Math.min(radius, Math.min(w, h) / 2));
                ctx.beginPath();
                ctx.moveTo(x + r, y);
                ctx.lineTo(x + w - r, y);
                ctx.quadraticCurveTo(x + w, y, x + w, y + r);
                ctx.lineTo(x + w, y + h - r);
                ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
                ctx.lineTo(x + r, y + h);
                ctx.quadraticCurveTo(x, y + h, x, y + h - r);
                ctx.lineTo(x, y + r);
                ctx.quadraticCurveTo(x, y, x + r, y);
                ctx.closePath();
            };

            const drawRoundedImage = (image, x, y, w, h, angle, radius) => {
                const r = Math.max(0, Math.min(radius, Math.min(w, h) / 2));
                ctx.save();
                ctx.translate(x, y);
                ctx.rotate(angle);
                ctx.shadowColor = "rgba(94, 68, 43, .18)";
                ctx.shadowBlur = 18;
                ctx.shadowOffsetY = 12;
                ctx.fillStyle = "rgba(255, 255, 255, .38)";
                roundedRectPath(-w / 2, -h / 2, w, h, r);
                ctx.fill();
                ctx.shadowColor = "transparent";

                roundedRectPath(-w / 2, -h / 2, w, h, r);
                ctx.clip();
                ctx.drawImage(image, -w / 2, -h / 2, w, h);

                const glass = ctx.createLinearGradient(-w / 2, -h / 2, w / 2, h / 2);
                glass.addColorStop(0, "rgba(255, 255, 255, .42)");
                glass.addColorStop(.34, "rgba(255, 255, 255, .08)");
                glass.addColorStop(.72, "rgba(255, 255, 255, 0)");
                glass.addColorStop(1, "rgba(94, 68, 43, .12)");
                ctx.fillStyle = glass;
                ctx.fillRect(-w / 2, -h / 2, w, h);

                ctx.restore();

                ctx.save();
                ctx.translate(x, y);
                ctx.rotate(angle);
                roundedRectPath(-w / 2, -h / 2, w, h, r);
                ctx.lineWidth = 2;
                ctx.strokeStyle = "rgba(94, 68, 43, .72)";
                ctx.stroke();
                ctx.restore();
            };

            const setCanvasSize = () => {
                const rect = root.getBoundingClientRect();
                const width = Math.max(1, rect.width);
                const height = Math.max(1, rect.height);
                const dpr = Math.max(1, window.devicePixelRatio || 1);
                canvas.width = Math.floor(width * dpr);
                canvas.height = Math.floor(height * dpr);
                canvas.style.width = `${width}px`;
                canvas.style.height = `${height}px`;
                size.width = width;
                size.height = height;
                size.dpr = dpr;
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            };

            const rebuildBoundaries = () => {
                if (boundaries.length > 0) {
                    Composite.remove(engine.world, boundaries.splice(0));
                }

                const wallThickness = 160;
                const floor = Bodies.rectangle(
                    size.width / 2,
                    size.height + wallThickness / 2,
                    size.width + wallThickness * 2,
                    wallThickness,
                    { isStatic: true, restitution: 0.05, friction: 0.9 }
                );
                const leftWall = Bodies.rectangle(
                    -wallThickness / 2,
                    size.height / 2,
                    wallThickness,
                    size.height * 3,
                    { isStatic: true, restitution: 0.05, friction: 0.9 }
                );
                const rightWall = Bodies.rectangle(
                    size.width + wallThickness / 2,
                    size.height / 2,
                    wallThickness,
                    size.height * 3,
                    { isStatic: true, restitution: 0.05, friction: 0.9 }
                );
                boundaries.push(floor, leftWall, rightWall);
                Composite.add(engine.world, boundaries);
            };

            const loadImages = async () => {
                const requests = images.map((img) => new Promise((resolve) => {
                    const el = new Image();
                    el.decoding = "async";
                    el.onload = () => resolve(el);
                    el.onerror = () => resolve(null);
                    el.src = img.src;
                }));
                const results = await Promise.all(requests);
                loadedImages.push(...results.filter(Boolean));
            };

            const spawnStickers = () => {
                const imageCount = Math.max(1, loadedImages.length);
                const bodies = [];

                for (let i = 0; i < stickerCount; i++) {
                    const image = loadedImages[i % imageCount];
                    const randomScale = 1 + randomInRange(-sizeRandomness, sizeRandomness);
                    const base = Math.max(44, stickerSize * randomScale);
                    const naturalRatio = image ? image.naturalWidth / Math.max(1, image.naturalHeight) : 1;
                    const w = base;
                    const h = base / naturalRatio;
                    const baseX = ((i + .5) / stickerCount) * size.width;
                    const x = baseX + randomInRange(-size.width * .1, size.width * .1);
                    const y = -i * (h * .7) - randomInRange(20, 180);
                    const angle = randomInRange(-.45, .45);

                    const body = Bodies.rectangle(x, y, w, h, {
                        restitution,
                        friction,
                        frictionStatic: Math.min(1, friction + .3),
                        frictionAir: .01 + friction * .03,
                        slop: .05,
                        sleepThreshold: 28,
                    });
                    Body.setAngle(body, angle);
                    Sleeping.set(body, false);

                    const sticker = {
                        body,
                        imageIndex: i % imageCount,
                        width: w,
                        height: h,
                    };
                    stickers.push(sticker);
                    bodies.push(body);
                }

                Composite.add(engine.world, bodies);
            };

            const getPointerWorld = (event) => {
                const rect = canvas.getBoundingClientRect();
                return {
                    x: event.clientX - rect.left,
                    y: event.clientY - rect.top,
                };
            };

            const onPointerDown = (event) => {
                const pos = getPointerWorld(event);
                const hit = Query.point(stickers.map((sticker) => sticker.body), pos);
                if (hit.length > 0) {
                    const body = hit[hit.length - 1];
                    drag.body = body;
                    drag.points = [{ ...pos, t: performance.now() }];
                    Sleeping.set(body, false);
                    canvas.setPointerCapture(event.pointerId);
                }
            };

            const onPointerMove = (event) => {
                if (!drag.body) return;
                const pos = getPointerWorld(event);
                const now = performance.now();
                drag.points.push({ ...pos, t: now });
                if (drag.points.length > 8) drag.points.shift();

                const stiffness = .22;
                const dx = pos.x - drag.body.position.x;
                const dy = pos.y - drag.body.position.y;
                Body.setVelocity(drag.body, {
                    x: dx * stiffness,
                    y: dy * stiffness,
                });
                Body.setAngularVelocity(drag.body, 0);
            };

            const onPointerUp = (event) => {
                if (!drag.body) return;
                const first = drag.points[0];
                const last = drag.points[drag.points.length - 1];
                if (first && last && last.t > first.t) {
                    const dt = last.t - first.t;
                    const vx = ((last.x - first.x) / dt) * 16.67 * throwPower;
                    const vy = ((last.y - first.y) / dt) * 16.67 * throwPower;
                    Body.setVelocity(drag.body, { x: vx, y: vy });
                }
                drag.body = null;
                drag.points = [];
                if (canvas.hasPointerCapture(event.pointerId)) {
                    canvas.releasePointerCapture(event.pointerId);
                }
            };

            const render = () => {
                ctx.clearRect(0, 0, size.width, size.height);
                ctx.fillStyle = "#FFFFFF";
                ctx.fillRect(0, 0, size.width, size.height);

                stickers.forEach((sticker) => {
                    const image = loadedImages[sticker.imageIndex % Math.max(1, loadedImages.length)];
                    if (!image) return;
                    drawRoundedImage(
                        image,
                        sticker.body.position.x,
                        sticker.body.position.y,
                        sticker.width,
                        sticker.height,
                        sticker.body.angle,
                        borderRadius
                    );
                });

                window.requestAnimationFrame(render);
            };

            const onBeforeUpdate = () => {
                if (drag.body) Sleeping.set(drag.body, false);
            };

            const setup = async () => {
                setCanvasSize();
                rebuildBoundaries();
                await loadImages();
                spawnStickers();
                Events.on(engine, "beforeUpdate", onBeforeUpdate);
                Runner.run(runner, engine);
                window.requestAnimationFrame(render);
            };

            const resizeObserver = new ResizeObserver(() => {
                setCanvasSize();
                rebuildBoundaries();
            });

            canvas.addEventListener("pointerdown", onPointerDown);
            canvas.addEventListener("pointermove", onPointerMove);
            canvas.addEventListener("pointerup", onPointerUp);
            canvas.addEventListener("pointercancel", onPointerUp);
            canvas.addEventListener("pointerleave", onPointerUp);
            resizeObserver.observe(root);
            setup();
        };

        const boot = () => {
            document.querySelectorAll(".bb-physics-wall").forEach(initPhysicsStickerWall);
        };

        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", boot);
        } else {
            boot();
        }
    })();
</script>
