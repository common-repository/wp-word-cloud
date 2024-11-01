jQuery(document).ready(function() {
    jQuery('.wp-word-cloud-container').each(function() {
        var container = jQuery(this);

        var tags = container.find('li');
        var width = container.width();

        var canvas = {
            container: container,
            objects: [],
            width: width,
            min_y: 0,
            max_y: 0
        };

        // PARAMETERS
        var radius_step = 10;
        var angle_step = 75;
        var font_sizes = [12,13,14,15,16,18,20,22,24];
        var start_color = container.data('color');
        var color_step = 10;
        // ----------

        // Adjust font sizes and colors
        tags.each(function() {
            var tag = jQuery(this);
            var weight = tag.data('weight');

            var font_size = font_sizes[weight];
            tag.css('font-size',font_size + 'px');

            var amount = (font_sizes.length - weight) * color_step;
            var color = wpwc_lightencolor(start_color, amount);
            tag.css('color', '#'+color);
        });

        // Positioning
        var angle = 45;
        var radius = 0;
        var center = {
            x: width/2,
            y: 0
        };

        tags.each(function() {
            var tag = jQuery(this);

            var object = wpwc_get_object_for(tag, center, radius, angle);

            while(wpwc_detect_overlap_with_canvas(object, canvas)) {
                radius += radius_step;
                object = wpwc_get_object_for(tag, center, radius, angle);
            }

            if(object['topleft']['y'] > canvas['max_y']) {
                canvas['max_y'] = object['topleft']['y'];
            }
            if(object['bottomright']['y'] < canvas['min_y']) {
                canvas['min_y'] = object['bottomright']['y'];
            }

            canvas['objects'].push(object);
            angle += angle_step;
        });

        wpwc_plot_canvas(canvas);
    });
});

function wpwc_get_object_for(tag, center, radius, angle)
{
    var x = center['x'] + radius * Math.cos(angle * Math.PI / 180);
    var y = center['y'] + radius * Math.sin(angle * Math.PI / 180);

    var topleft_x = Math.round(x - tag.width()/2);
    var topleft_y = Math.round(y + tag.height()/2);

    var bottomright_x = Math.round(topleft_x + tag.width());
    var bottomright_y = Math.round(topleft_y - tag.height());

    return {
        object: tag,
        topleft: {
            x: topleft_x,
            y: topleft_y
        },
        bottomright: {
            x: bottomright_x,
            y: bottomright_y
        }
    };
}
function wpwc_detect_overlap_with_canvas(object,canvas)
{
    var overlap = false;

    if(object['topleft']['x'] < 0 || object['bottomright']['x'] > canvas['width']) {
        overlap = true
    } else {
        for (var i = 0; i < canvas['objects'].length; i++) {
            if(wpwc_detect_overlap_with_object(object, canvas['objects'][i])) {
                overlap = true;
                break;
            }
        }
    }

    return overlap;
}

function wpwc_detect_overlap_with_object(obj1,obj2)
{
    if(obj1['topleft']['x'] >= obj2['bottomright']['x']
        || obj1['bottomright']['x'] <= obj2['topleft']['x']
        || obj1['topleft']['y'] <= obj2['bottomright']['y']
        || obj1['bottomright']['y'] >= obj2['topleft']['y'])
    {
        return false;
    }

    return true;
}

function wpwc_plot_canvas(canvas)
{
    var height = canvas['max_y']-canvas['min_y'];

    canvas['container'].height(height);

    for (var i = 0; i < canvas['objects'].length; i++) {
        var object = canvas['objects'][i];

        var left = object['topleft']['x'];
        var bottom = object['bottomright']['y'] - canvas['min_y'];

        object['object'].css('left', left+'px');
        object['object'].css('bottom', bottom+'px');
    }
}

function wpwc_lightencolor(color, amount) {
    var num = parseInt(color, 16);

    var r = (num >> 16) + amount;
    if (r > 255) r = 255;
    else if (r < 0) r = 0;

    var g = ((num >> 8) & 0x00FF) + amount;

    if (g > 255) g = 255;
    else if (g < 0) g = 0;

    var b = (num & 0x0000FF) + amount;

    if (b > 255) b = 255;
    else if (b < 0 ) b = 0;

    var rStr = (r.toString(16).length < 2)?'0'+r.toString(16):r.toString(16);
    var gStr = (g.toString(16).length < 2)?'0'+g.toString(16):g.toString(16);
    var bStr = (b.toString(16).length < 2)?'0'+b.toString(16):b.toString(16);

    return rStr + gStr + bStr;
}