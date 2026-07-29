<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --ticker-color: {{ $textColor }};
            --ticker-background: {{ $backgroundColor }};
            --ticker-font: {!! $fontFamily !!};
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
            overflow: hidden;
            background: transparent;
        }

        .ticker {
            position: fixed;
            right: 0;
            bottom: 4%;
            left: 0;
            height: 92px;
            overflow: hidden;
            border-top: 2px solid rgba(245, 158, 11, 0.9);
            border-bottom: 1px solid rgba(255, 255, 255, 0.18);
            background: var(--ticker-background);
            box-shadow: 0 12px 34px rgba(0, 0, 0, 0.28);
        }

        .ticker::before {
            position: absolute;
            z-index: 2;
            top: 0;
            bottom: 0;
            left: 0;
            width: 20px;
            content: "";
            background: #f59e0b;
        }

        .ticker__track {
            display: inline-flex;
            min-width: max-content;
            height: 100%;
            align-items: center;
            padding-right: 4rem;
            padding-left: 100vw;
            color: var(--ticker-color);
            font-family: var(--ticker-font);
            font-size: clamp(30px, 2.25vw, 44px);
            font-weight: 700;
            letter-spacing: 0.01em;
            line-height: 1;
            white-space: nowrap;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.7);
            animation: ticker-scroll 18s linear {{ $loopEnabled ? 'infinite' : '1' }};
        }

        @keyframes ticker-scroll {
            from {
                transform: translateX(0);
            }

            to {
                transform: translateX(-100%);
            }
        }
    </style>
</head>
<body>
    <div class="ticker">
        <div class="ticker__track" data-speed="{{ $tickerSpeed }}">{{ $text }}</div>
    </div>
    <script>
        const track = document.querySelector('.ticker__track');
        const speed = Number(track.dataset.speed) || 120;

        const updateDuration = () => {
            const distance = track.scrollWidth;
            track.style.animationDuration = `${Math.max(3, distance / speed)}s`;
        };

        updateDuration();
        window.addEventListener('resize', updateDuration);
    </script>
</body>
</html>
