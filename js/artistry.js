var artistry = {
    maxImageWidth: 1000,
    triggerArtistry: function() {
      return (document.getElementById("srcimg") && document.getElementById("canvas"));
    },
    presenter: function(imgID, canvasID) {
        if (document.getElementById(imgID)) {
            var oImg = document.getElementById(imgID),
                oBox = oImg.parentNode.parentNode,
                imgWidth = oImg.naturalWidth,
                imgHeight = oImg.naturalHeight,
                boxHeight = simpleCS.getHeight(oBox.id),
                boxWidth = simpleCS.getWidth(oBox.id),
                winHeight = simpleCS.getHeight("window"),
                winWidth = simpleCS.getWidth("window"),
                maxImgWidth = artistry.maxImageWidth,
                imgLandscape = ((imgWidth / imgHeight) > 1),
                minWinWidth = ((imgWidth > maxImgWidth ? maxImgWidth : imgWidth) + (parseInt(simpleCS.getElementStyleProp(oBox.id, "padding-left")) + parseInt(simpleCS.getElementStyleProp(oBox.id, "padding-right")))),
                canvas = document.getElementById(canvasID);
            oBox.parentNode.className = oBox.parentNode.className.replace("artistry", "").trim() + " artistry";
            oBox.parentNode.className = oBox.parentNode.className.replace("visualoverflow", "").trim() + " visualoverflow";
            oBox.parentNode.parentNode.className = oBox.parentNode.parentNode.className.replace("visualoverflow", "").trim() + " visualoverflow";
            oBox.parentNode.parentNode.parentNode.className = oBox.parentNode.parentNode.parentNode.className.replace("visualoverflow", "").trim() + " visualoverflow";
//            if (((winWidth) / winHeight) > (imgWidth / imgHeight)) {
            if (winWidth > winHeight) {
                if (winWidth <= minWinWidth) {
                    oImg.style.width = "100%";
                    oImg.style.height = "auto";
                    oBox.style.width = "100%";
                    oBox.style.padding = "0px";
                    oBox.style.marginLeft = "0px";
                    oBox.style.marginTop = "0px";
                    oBox.style.left = "0px";
                    canvas.parentNode.style.visibility = "hidden"
                } else {
                    oImg.style.width = (imgWidth > artistry.maxImageWidth ? artistry.maxImageWidth : imgWidth) + "px";
                    oImg.style.height = "auto";
                    oBox.style.width = simpleCS.getWidth(oImg.id) + "px";
                    oBox.style.padding = "";
                    oBox.style.marginLeft = (0 - (parseInt(simpleCS.getWidth(oImg.id) / 2) + (parseInt(simpleCS.getElementStyleProp(oBox.id, "padding-left")) + parseInt(simpleCS.getElementStyleProp(oBox.id, "padding-right"))) - (boxHeight > winHeight ? 18 : 0))) + "px";
                    oBox.style.marginTop = "";
                    oBox.style.left = "50%";
                    canvas.parentNode.style.visibility = "visible";
                    artistry.stackBlurImage(imgID, canvasID, 40, true)
                }
            } else {
//            console.log(typeof winWidth +" - "+ typeof winHeight);
                oImg.style.width = "100%";
                oImg.style.height = "auto";
                oBox.style.width = "98%";
                oBox.style.padding = "0px";
                oBox.style.marginLeft = "1%";
                oBox.style.marginTop = "0px";
                oBox.style.left = "0px";
                canvas.parentNode.style.visibility = "hidden"
            }
            oBox.style.visibility = "visible"
        }
    },
    stackBlurImage: function(imageID, canvasID, radius, blurAlphaChannel) {
        var img = document.getElementById(imageID),
            w_img = img.naturalWidth,
            h_img = img.naturalHeight,
            x_scanStart = parseInt(w_img / 2),
            y_scanStart = parseInt(h_img / 2),
            x_scanEnd = w_img - x_scanStart,
            y_scanEnd = h_img - y_scanStart,
            canvas = document.getElementById(canvasID),
            winWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth,
            winHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
        if ((winWidth / winHeight) > (w_img / h_img)) {
            var w = winWidth,
                h = parseInt(h_img * (w / w_img))
        } else {
            var h = winHeight,
                w = parseInt(w_img * (h / h_img))
        }
        canvas.style.width = w + "px";
        canvas.style.height = h + "px";
        canvas.width = w;
        canvas.height = h;
        var context = canvas.getContext("2d");
        context.clearRect(0, 0, w, h);
        context.drawImage(img, x_scanStart, y_scanStart, x_scanEnd, y_scanEnd, 0, 0, w, h);
        radius = (isNaN(radius) || radius < 1) ? 0 : (radius > 180 ? 180 : radius);
        if (blurAlphaChannel) {
            artistry.stackBlurCanvasRGBA(canvasID, 0, 0, w, h, radius)
        } else {
            artistry.stackBlurCanvasRGB(canvasID, 0, 0, w, h, radius)
        }
    },
    stackBlurCanvasRGBA: function(id, top_x, top_y, width, height, radius) {
        var canvas = document.getElementById(id),
            context = canvas.getContext("2d"),
            imageData;
        try {
            imageData = context.getImageData(top_x, top_y, width, height)
        } catch (e) {
            alert("Cannot access image");
            throw new Error("unable to access image data: " + e)
        }
        var pixels = imageData.data,
            x, y, i, p, yp, yi, yw, r_sum, g_sum, b_sum, a_sum, r_out_sum, g_out_sum, b_out_sum, a_out_sum, r_in_sum, g_in_sum, b_in_sum, a_in_sum, pr, pg, pb, pa, rbs, div = radius + radius + 1,
            w4 = width << 2,
            widthMinus1 = width - 1,
            heightMinus1 = height - 1,
            radiusPlus1 = radius + 1,
            sumFactor = radiusPlus1 * (radiusPlus1 + 1) / 2,
            stackStart = new artistry.BlurStack(),
            stack = stackStart;
        for (i = 1; i < div; i += 1) {
            stack = stack.next = new artistry.BlurStack();
            if (i == radiusPlus1) {
                var stackEnd = stack
            }
        }
        stack.next = stackStart;
        var stackIn = null,
            stackOut = null;
        yw = yi = 0;
        var mul_sum = artistry.mul_table[radius],
            shg_sum = artistry.shg_table[radius];
        for (y = 0; y < height; y += 1) {
            r_in_sum = g_in_sum = b_in_sum = a_in_sum = r_sum = g_sum = b_sum = a_sum = 0;
            r_out_sum = radiusPlus1 * (pr = pixels[yi]);
            g_out_sum = radiusPlus1 * (pg = pixels[yi + 1]);
            b_out_sum = radiusPlus1 * (pb = pixels[yi + 2]);
            a_out_sum = radiusPlus1 * (pa = pixels[yi + 3]);
            r_sum += sumFactor * pr;
            g_sum += sumFactor * pg;
            b_sum += sumFactor * pb;
            a_sum += sumFactor * pa;
            stack = stackStart;
            for (i = 0; i < radiusPlus1; i += 1) {
                stack.r = pr;
                stack.g = pg;
                stack.b = pb;
                stack.a = pa;
                stack = stack.next
            }
            for (i = 1; i < radiusPlus1; i += 1) {
                p = yi + ((widthMinus1 < i ? widthMinus1 : i) << 2);
                r_sum += (stack.r = (pr = pixels[p])) * (rbs = radiusPlus1 - i);
                g_sum += (stack.g = (pg = pixels[p + 1])) * rbs;
                b_sum += (stack.b = (pb = pixels[p + 2])) * rbs;
                a_sum += (stack.a = (pa = pixels[p + 3])) * rbs;
                r_in_sum += pr;
                g_in_sum += pg;
                b_in_sum += pb;
                a_in_sum += pa;
                stack = stack.next
            }
            stackIn = stackStart;
            stackOut = stackEnd;
            for (x = 0; x < width; x += 1) {
                pixels[yi + 3] = pa = (a_sum * mul_sum) >> shg_sum;
                if (pa != 0) {
                    pa = 255 / pa;
                    pixels[yi] = ((r_sum * mul_sum) >> shg_sum) * pa;
                    pixels[yi + 1] = ((g_sum * mul_sum) >> shg_sum) * pa;
                    pixels[yi + 2] = ((b_sum * mul_sum) >> shg_sum) * pa
                } else {
                    pixels[yi] = pixels[yi + 1] = pixels[yi + 2] = 0
                }
                r_sum -= r_out_sum;
                g_sum -= g_out_sum;
                b_sum -= b_out_sum;
                a_sum -= a_out_sum;
                r_out_sum -= stackIn.r;
                g_out_sum -= stackIn.g;
                b_out_sum -= stackIn.b;
                a_out_sum -= stackIn.a;
                p = (yw + ((p = x + radius + 1) < widthMinus1 ? p : widthMinus1)) << 2;
                r_in_sum += (stackIn.r = pixels[p]);
                g_in_sum += (stackIn.g = pixels[p + 1]);
                b_in_sum += (stackIn.b = pixels[p + 2]);
                a_in_sum += (stackIn.a = pixels[p + 3]);
                r_sum += r_in_sum;
                g_sum += g_in_sum;
                b_sum += b_in_sum;
                a_sum += a_in_sum;
                stackIn = stackIn.next;
                r_out_sum += (pr = stackOut.r);
                g_out_sum += (pg = stackOut.g);
                b_out_sum += (pb = stackOut.b);
                a_out_sum += (pa = stackOut.a);
                r_in_sum -= pr;
                g_in_sum -= pg;
                b_in_sum -= pb;
                a_in_sum -= pa;
                stackOut = stackOut.next;
                yi += 4
            }
            yw += width
        }
        for (x = 0; x < width; x += 1) {
            g_in_sum = b_in_sum = a_in_sum = r_in_sum = g_sum = b_sum = a_sum = r_sum = 0;
            yi = x << 2;
            r_out_sum = radiusPlus1 * (pr = pixels[yi]);
            g_out_sum = radiusPlus1 * (pg = pixels[yi + 1]);
            b_out_sum = radiusPlus1 * (pb = pixels[yi + 2]);
            a_out_sum = radiusPlus1 * (pa = pixels[yi + 3]);
            r_sum += sumFactor * pr;
            g_sum += sumFactor * pg;
            b_sum += sumFactor * pb;
            a_sum += sumFactor * pa;
            stack = stackStart;
            for (i = 0; i < radiusPlus1; i += 1) {
                stack.r = pr;
                stack.g = pg;
                stack.b = pb;
                stack.a = pa;
                stack = stack.next
            }
            yp = width;
            for (i = 1; i <= radius; i += 1) {
                yi = (yp + x) << 2;
                r_sum += (stack.r = (pr = pixels[yi])) * (rbs = radiusPlus1 - i);
                g_sum += (stack.g = (pg = pixels[yi + 1])) * rbs;
                b_sum += (stack.b = (pb = pixels[yi + 2])) * rbs;
                a_sum += (stack.a = (pa = pixels[yi + 3])) * rbs;
                r_in_sum += pr;
                g_in_sum += pg;
                b_in_sum += pb;
                a_in_sum += pa;
                stack = stack.next;
                if (i < heightMinus1) {
                    yp += width
                }
            }
            yi = x;
            stackIn = stackStart;
            stackOut = stackEnd;
            for (y = 0; y < height; y += 1) {
                p = yi << 2;
                pixels[p + 3] = pa = (a_sum * mul_sum) >> shg_sum;
                if (pa > 0) {
                    pa = 255 / pa;
                    pixels[p] = ((r_sum * mul_sum) >> shg_sum) * pa;
                    pixels[p + 1] = ((g_sum * mul_sum) >> shg_sum) * pa;
                    pixels[p + 2] = ((b_sum * mul_sum) >> shg_sum) * pa
                } else {
                    pixels[p] = pixels[p + 1] = pixels[p + 2] = 0
                }
                r_sum -= r_out_sum;
                g_sum -= g_out_sum;
                b_sum -= b_out_sum;
                a_sum -= a_out_sum;
                r_out_sum -= stackIn.r;
                g_out_sum -= stackIn.g;
                b_out_sum -= stackIn.b;
                a_out_sum -= stackIn.a;
                p = (x + (((p = y + radiusPlus1) < heightMinus1 ? p : heightMinus1) * width)) << 2;
                r_sum += (r_in_sum += (stackIn.r = pixels[p]));
                g_sum += (g_in_sum += (stackIn.g = pixels[p + 1]));
                b_sum += (b_in_sum += (stackIn.b = pixels[p + 2]));
                a_sum += (a_in_sum += (stackIn.a = pixels[p + 3]));
                stackIn = stackIn.next;
                r_out_sum += (pr = stackOut.r);
                g_out_sum += (pg = stackOut.g);
                b_out_sum += (pb = stackOut.b);
                a_out_sum += (pa = stackOut.a);
                r_in_sum -= pr;
                g_in_sum -= pg;
                b_in_sum -= pb;
                a_in_sum -= pa;
                stackOut = stackOut.next;
                yi += width
            }
        }
        context.putImageData(imageData, top_x, top_y)
    },
    stackBlurCanvasRGB: function(id, top_x, top_y, width, height, radius) {
        if (isNaN(radius) || radius < 1) {
            return
        }
        radius |= 0;
        var canvas = document.getElementById(id),
            context = canvas.getContext("2d"),
            imageData;
        try {
            imageData = context.getImageData(top_x, top_y, width, height)
        } catch (e) {
            alert("Cannot access image");
            throw new Error("unable to access image data: " + e)
        }
        var pixels = imageData.data,
            x, y, i, p, yp, yi, yw, r_sum, g_sum, b_sum, r_out_sum, g_out_sum, b_out_sum, r_in_sum, g_in_sum, b_in_sum, pr, pg, pb, rbs, div = radius + radius + 1,
            w4 = width << 2,
            widthMinus1 = width - 1,
            heightMinus1 = height - 1,
            radiusPlus1 = radius + 1,
            sumFactor = radiusPlus1 * (radiusPlus1 + 1) / 2,
            stackStart = new artistry.BlurStack(),
            stack = stackStart;
        for (i = 1; i < div; i += 1) {
            stack = stack.next = new artistry.BlurStack();
            if (i == radiusPlus1) {
                var stackEnd = stack
            }
        }
        stack.next = stackStart;
        var stackIn = null,
            stackOut = null;
        yw = yi = 0;
        var mul_sum = artistry.mul_table[radius],
            shg_sum = artistry.shg_table[radius];
        for (y = 0; y < height; y += 1) {
            r_in_sum = g_in_sum = b_in_sum = r_sum = g_sum = b_sum = 0;
            r_out_sum = radiusPlus1 * (pr = pixels[yi]);
            g_out_sum = radiusPlus1 * (pg = pixels[yi + 1]);
            b_out_sum = radiusPlus1 * (pb = pixels[yi + 2]);
            r_sum += sumFactor * pr;
            g_sum += sumFactor * pg;
            b_sum += sumFactor * pb;
            stack = stackStart;
            for (i = 0; i < radiusPlus1; i += 1) {
                stack.r = pr;
                stack.g = pg;
                stack.b = pb;
                stack = stack.next
            }
            for (i = 1; i < radiusPlus1; i += 1) {
                p = yi + ((widthMinus1 < i ? widthMinus1 : i) << 2);
                r_sum += (stack.r = (pr = pixels[p])) * (rbs = radiusPlus1 - i);
                g_sum += (stack.g = (pg = pixels[p + 1])) * rbs;
                b_sum += (stack.b = (pb = pixels[p + 2])) * rbs;
                r_in_sum += pr;
                g_in_sum += pg;
                b_in_sum += pb;
                stack = stack.next
            }
            stackIn = stackStart;
            stackOut = stackEnd;
            for (x = 0; x < width; x += 1) {
                pixels[yi] = (r_sum * mul_sum) >> shg_sum;
                pixels[yi + 1] = (g_sum * mul_sum) >> shg_sum;
                pixels[yi + 2] = (b_sum * mul_sum) >> shg_sum;
                r_sum -= r_out_sum;
                g_sum -= g_out_sum;
                b_sum -= b_out_sum;
                r_out_sum -= stackIn.r;
                g_out_sum -= stackIn.g;
                b_out_sum -= stackIn.b;
                p = (yw + ((p = x + radius + 1) < widthMinus1 ? p : widthMinus1)) << 2;
                r_in_sum += (stackIn.r = pixels[p]);
                g_in_sum += (stackIn.g = pixels[p + 1]);
                b_in_sum += (stackIn.b = pixels[p + 2]);
                r_sum += r_in_sum;
                g_sum += g_in_sum;
                b_sum += b_in_sum;
                stackIn = stackIn.next;
                r_out_sum += (pr = stackOut.r);
                g_out_sum += (pg = stackOut.g);
                b_out_sum += (pb = stackOut.b);
                r_in_sum -= pr;
                g_in_sum -= pg;
                b_in_sum -= pb;
                stackOut = stackOut.next;
                yi += 4
            }
            yw += width
        }
        for (x = 0; x < width; x += 1) {
            g_in_sum = b_in_sum = r_in_sum = g_sum = b_sum = r_sum = 0;
            yi = x << 2;
            r_out_sum = radiusPlus1 * (pr = pixels[yi]);
            g_out_sum = radiusPlus1 * (pg = pixels[yi + 1]);
            b_out_sum = radiusPlus1 * (pb = pixels[yi + 2]);
            r_sum += sumFactor * pr;
            g_sum += sumFactor * pg;
            b_sum += sumFactor * pb;
            stack = stackStart;
            for (i = 0; i < radiusPlus1; i += 1) {
                stack.r = pr;
                stack.g = pg;
                stack.b = pb;
                stack = stack.next
            }
            yp = width;
            for (i = 1; i <= radius; i += 1) {
                yi = (yp + x) << 2;
                r_sum += (stack.r = (pr = pixels[yi])) * (rbs = radiusPlus1 - i);
                g_sum += (stack.g = (pg = pixels[yi + 1])) * rbs;
                b_sum += (stack.b = (pb = pixels[yi + 2])) * rbs;
                r_in_sum += pr;
                g_in_sum += pg;
                b_in_sum += pb;
                stack = stack.next;
                if (i < heightMinus1) {
                    yp += width
                }
            }
            yi = x;
            stackIn = stackStart;
            stackOut = stackEnd;
            for (y = 0; y < height; y += 1) {
                p = yi << 2;
                pixels[p] = (r_sum * mul_sum) >> shg_sum;
                pixels[p + 1] = (g_sum * mul_sum) >> shg_sum;
                pixels[p + 2] = (b_sum * mul_sum) >> shg_sum;
                r_sum -= r_out_sum;
                g_sum -= g_out_sum;
                b_sum -= b_out_sum;
                r_out_sum -= stackIn.r;
                g_out_sum -= stackIn.g;
                b_out_sum -= stackIn.b;
                p = (x + (((p = y + radiusPlus1) < heightMinus1 ? p : heightMinus1) * width)) << 2;
                r_sum += (r_in_sum += (stackIn.r = pixels[p]));
                g_sum += (g_in_sum += (stackIn.g = pixels[p + 1]));
                b_sum += (b_in_sum += (stackIn.b = pixels[p + 2]));
                stackIn = stackIn.next;
                r_out_sum += (pr = stackOut.r);
                g_out_sum += (pg = stackOut.g);
                b_out_sum += (pb = stackOut.b);
                r_in_sum -= pr;
                g_in_sum -= pg;
                b_in_sum -= pb;
                stackOut = stackOut.next;
                yi += width
            }
        }
        context.putImageData(imageData, top_x, top_y, 0, 0, 2000, 3000);
        carousel()
    },
    BlurStack: function() {
        this.r = 0;
        this.g = 0;
        this.b = 0;
        this.a = 0;
        this.next = null
    },
    mul_table: [512, 512, 456, 512, 328, 456, 335, 512, 405, 328, 271, 456, 388, 335, 292, 512, 454, 405, 364, 328, 298, 271, 496, 456, 420, 388, 360, 335, 312, 292, 273, 512, 482, 454, 428, 405, 383, 364, 345, 328, 312, 298, 284, 271, 259, 496, 475, 456, 437, 420, 404, 388, 374, 360, 347, 335, 323, 312, 302, 292, 282, 273, 265, 512, 497, 482, 468, 454, 441, 428, 417, 405, 394, 383, 373, 364, 354, 345, 337, 328, 320, 312, 305, 298, 291, 284, 278, 271, 265, 259, 507, 496, 485, 475, 465, 456, 446, 437, 428, 420, 412, 404, 396, 388, 381, 374, 367, 360, 354, 347, 341, 335, 329, 323, 318, 312, 307, 302, 297, 292, 287, 282, 278, 273, 269, 265, 261, 512, 505, 497, 489, 482, 475, 468, 461, 454, 447, 441, 435, 428, 422, 417, 411, 405, 399, 394, 389, 383, 378, 373, 368, 364, 359, 354, 350, 345, 341, 337, 332, 328, 324, 320, 316, 312, 309, 305, 301, 298, 294, 291, 287, 284, 281, 278, 274, 271, 268, 265, 262, 259, 257, 507, 501, 496, 491, 485, 480, 475, 470, 465, 460, 456, 451, 446, 442, 437, 433, 428, 424, 420, 416, 412, 408, 404, 400, 396, 392, 388, 385, 381, 377, 374, 370, 367, 363, 360, 357, 354, 350, 347, 344, 341, 338, 335, 332, 329, 326, 323, 320, 318, 315, 312, 310, 307, 304, 302, 299, 297, 294, 292, 289, 287, 285, 282, 280, 278, 275, 273, 271, 269, 267, 265, 263, 261, 259],
    shg_table: [9, 11, 12, 13, 13, 14, 14, 15, 15, 15, 15, 16, 16, 16, 16, 17, 17, 17, 17, 17, 17, 17, 18, 18, 18, 18, 18, 18, 18, 18, 18, 19, 19, 19, 19, 19, 19, 19, 19, 19, 19, 19, 19, 19, 19, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 21, 21, 21, 21, 21, 21, 21, 21, 21, 21, 21, 21, 21, 21, 21, 21, 21, 21, 21, 21, 21, 21, 21, 21, 21, 21, 21, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 23, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24, 24]
};
var carousel = {
    previewHeight: 200,
    initialised: false,
    e: null,
    dataCode: 0,
    x: 0,
    y: 0,
    prevX: 0,
    prevY: 0,
    mouseDown: false,
    evPropagated: false,
    scrollStart: new Date().getTime(),
    buildStrip: function() {},
    setFlags: function() {
        if (document.documentElement) {
            this.dataCode = 3
        } else if (document.body && typeof document.body.scrollTop != 'undefined') {
            this.dataCode = 2
        } else if (this.e && this.e.pageX != 'undefined') {
            this.dataCode = 1
        }
        this.initialised = true
    },
    create: function(error, response, obj) {
        docFrag = document.createDocumentFragment();
        box = document.createElement("div");
        box.setAttribute("class", "carousel-box");
        strip = document.createElement("div");
        strip.setAttribute("id", "carousel-strip");
        strip.setAttribute("class", "carousel-strip");
        data = JSON.parse(response);
        for (var x = 0; x < data.artObjects.length; x += 1) {
            art = document.createElement("div");
            art.setAttribute("class", "carousel-art");
            artLink = document.createElement("a");
            artLink.setAttribute("href", data.artObjects[x].location);
            artLink.setAttribute("class","image-link");
            artLink.setAttribute("title", data.artObjects[x].title);
            artLink.onclick = function(event) {
                event.preventDefault();
            };
            artImage = document.createElement("img");
            artImage.setAttribute("src", data.artObjects[x].imgsrc);
            artImage.setAttribute("class", "carousel-art-img");
            artLink.appendChild(artImage);
            art.appendChild(artLink);
            docFrag.appendChild(art);
        }
        strip.appendChild(docFrag);
        box.appendChild(strip);
        obj.appendChild(box);
        carousel.previewHeight = parseInt(simpleCS.getClassStyleProp("img","carousel-art-img", "height")) + parseInt(simpleCS.getClassStyleProp("div","carousel-art", "margin-top")) + parseInt(simpleCS.getClassStyleProp("div","carousel-art", "margin-bottom"));

//console.log(parseInt(simpleCS.getClassStyleProp("div","carousel-art", "margin-top")) + parseInt(simpleCS.getClassStyleProp("div","carousel-art", "margin-bottom")));

        carousel.addHandlers(strip);
        carousel.animate(obj);
    },
    addHandlers: function(obj) {
        if (!document.getElementById && document.captureEvents && Event) {
            document.captureEvents(Event.MOUSEMOVE)
        }
        carousel.addToHandler(document, 'onmousemove', function() {
            carousel.getMousePosition(arguments[0], obj)
        });
        carousel.addToHandler(document, 'onmousedown', function() {
            carousel.mouseDown = true;
            return false
        });
        carousel.addToHandler(document, 'onmouseup', function() {
            carousel.mouseDown = false
        });
        carousel.addToHandler(document, 'onselectstart', function() {
            return false
        })
    },
    init: function(parent) {
        if (document.getElementById("srcimg") && document.getElementById("canvas")) {

// presenter
            artistry.presenter("srcimg", "canvas");
            simpleCS.addevent(window, "resize", (function(i, d) {
                return function() {
                    artistry.presenter(i, d)
                }
            })("srcimg", "canvas"), false);
// carousel
            simpleCS.requestJSON(window.location.protocol + '//' + window.location.hostname + '/api/v1/artistry' + simpleCS.pop_url(window.location.pathname), carousel.create, parent);
        }
    },
    animate: function(parent) {
        var style = document.createElement("style"),
            parentHeight = parseInt(simpleCS.getElementStyleProp(parent.id, 'height')) + carousel.previewHeight,
            hoofdmenu = document.getElementById("hoofdmenu"),
            mainmenubar = parent;
        style.type = "text/css";
        style.innerHTML = "#" + parent.id + ":hover {height:" + parentHeight + "px !important;}";
        document.getElementsByTagName('head')[0].appendChild(style);

        simpleCS.addevent(mainmenubar, "click", function() {
          if(mainmenubar.hasAttribute("style")) {
            mainmenubar.removeAttribute('style');
            if(hoofdmenu.classList.contains('transDirect')) {
              hoofdmenu.classList.remove('transDirect');
              hoofdmenu.classList.add('transDelay');
            }
          } else {            
            if(hoofdmenu.classList.contains('transDelay')) {
              hoofdmenu.classList.remove('transDelay');
              hoofdmenu.classList.add('transDirect');
            }
            mainmenubar.style.height = parentHeight + "px";
          }
        }, false);
        simpleCS.addevent(window, "scroll", function() {
          if (simpleCS.getDocHeight() <= ((simpleCS.getScrollXY()[1] + window.innerHeight) + 20)) {
            if(hoofdmenu.classList.contains('transDelay')) {
              hoofdmenu.classList.remove('transDelay');
              hoofdmenu.classList.add('transDirect');
            }
            mainmenubar.style.height = parentHeight + "px";
          } else {
            mainmenubar.removeAttribute('style');
            if(hoofdmenu.classList.contains('transDirect')) {
              hoofdmenu.classList.remove('transDirect');
              hoofdmenu.classList.add('transDelay');
            }
          }
        }, false);
        simpleCS.addevent(window, "resize", function() {
          if (simpleCS.getDocHeight() <= ((simpleCS.getScrollXY()[1] + window.innerHeight) + 20)) {
            if(hoofdmenu.classList.contains('transDelay')) {
              hoofdmenu.classList.remove('transDelay');
              hoofdmenu.classList.add('transDirect');
            }
            mainmenubar.style.height = parentHeight + "px";
          } else {
            mainmenubar.removeAttribute('style');
            if(hoofdmenu.classList.contains('transDirect')) {
              hoofdmenu.classList.remove('transDirect');
              hoofdmenu.classList.add('transDelay');
            }
          }
        }, false);
    },
    scrollList: function() {
        carousel.scrollStart = new Date().getTime()
    },
    canClickInList: function() {
        var diff = new Date().getTime() - carousel.scrollStart;
        if (diff > 300) {
            return true
        } else {
            return false
        }
    },
    getMousePosition: function(e, obj) {
        if (!e) {
            this.e = event
        } else {
            this.e = e
        }
        if (!this.initialised) {
            this.setFlags()
        }
        switch (this.dataCode) {
            case 3:
                this.x = Math.max(document.documentElement.scrollLeft, document.body.scrollLeft) - this.e.clientX;
                break;
            case 2:
                this.x = document.body.scrollLeft - this.e.clientX;
                break;
            case 1:
                this.x = this.e.pageX;
                break
        }
        var bla = obj.children;
        if (this.mouseDown && (this.x != this.prevX || this.y != this.prevY)) {
            for (var x = 0; x < bla.length; x += 1) {
                simpleCS.delevent(bla[x].children[0], "click", carousel.view, false)
            }
            carousel.evPropagated = false;
            obj.scrollLeft += (this.x - this.prevX);
            obj.scrollTop += (this.y - this.prevY)
        } else if (!carousel.evPropagated && carousel.canClickInList()) {
            for (var y = 0; y < bla.length; y += 1) {
                simpleCS.addevent(bla[y].children[0], "click", carousel.view, false)
            }
            carousel.evPropagated = true
        }
        this.prevX = this.x;
        this.prevY = this.y
    },
    view: function(e) {
        if (!e) {
            this.e = event
        } else {
            this.e = e
        }
        window.location.href = e.target.parentNode.href
    },
    addToHandler: function(obj, evt, func) {
        if (obj[evt]) {
            obj[evt] = function(f, g) {
                return function() {
                    f.apply(this, arguments);
                    return g.apply(this, arguments)
                }
            }(func, obj[evt])
        } else {
            obj[evt] = func
        }
    }
};