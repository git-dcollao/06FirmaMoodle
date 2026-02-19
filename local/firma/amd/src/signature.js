define([], function() {
    const DEFAULT_LINE_WIDTH = 2;

    const getPosition = (canvas, event) => {
        const rect = canvas.getBoundingClientRect();
        const touch = event.touches ? event.touches[0] : null;
        const clientX = touch ? touch.clientX : event.clientX;
        const clientY = touch ? touch.clientY : event.clientY;
        return {
            x: clientX - rect.left,
            y: clientY - rect.top,
        };
    };

    const clearCanvas = (canvas, ctx) => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    };

    const init = (config) => {
        const canvas = document.getElementById(config.canvasid);
        const input = document.getElementById(config.inputid);
        const submit = document.getElementById(config.submitid);
        const clearBtn = document.getElementById(config.clearid);
        if (!canvas || !input || !submit || !clearBtn) {
            return;
        }

        const ctx = canvas.getContext('2d');
        ctx.lineWidth = DEFAULT_LINE_WIDTH;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#111';
        clearCanvas(canvas, ctx);

        let drawing = false;
        let hasStroke = false;

        const startDrawing = (event) => {
            event.preventDefault();
            drawing = true;
            const pos = getPosition(canvas, event);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
        };

        const draw = (event) => {
            if (!drawing) {
                return;
            }
            event.preventDefault();
            const pos = getPosition(canvas, event);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
            hasStroke = true;
        };

        const endDrawing = (event) => {
            if (!drawing) {
                return;
            }
            event.preventDefault();
            drawing = false;
            ctx.closePath();
        };

        ['mousedown', 'touchstart'].forEach(evt => canvas.addEventListener(evt, startDrawing));
        ['mousemove', 'touchmove'].forEach(evt => canvas.addEventListener(evt, draw));
        ['mouseup', 'mouseleave', 'touchend', 'touchcancel'].forEach(evt => canvas.addEventListener(evt, endDrawing));

        clearBtn.addEventListener('click', (event) => {
            event.preventDefault();
            clearCanvas(canvas, ctx);
            hasStroke = false;
            input.value = '';
        });

        submit.addEventListener('click', (event) => {
            if (!hasStroke) {
                event.preventDefault();
                window.alert(config.message || 'Signature required');
                return;
            }
            input.value = canvas.toDataURL('image/png');
        });
    };

    return { init };
});
