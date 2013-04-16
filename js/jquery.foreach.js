/** http://stackoverflow.com/questions/182630/jquery-tips-and-tricks/2750689#2750689 **/

jQuery.forEach = function (in_array, in_pause_ms, in_callback)
{
    if (!in_array.length) return; // make sure array was sent

    var i = 0; // starting index

    bgEach(); // call the function

    function bgEach()
    {
        if (in_callback.call(in_array[i], i, in_array[i]) !== false)
        {
            i++; // move to next item

            if (i < in_array.length) setTimeout(bgEach, in_pause_ms);
        }
    }

    return in_array; // returns array
};


jQuery.fn.forEach = function (in_callback, in_optional_pause_ms)
{
    if (!in_optional_pause_ms) in_optional_pause_ms = 10; // default

    return jQuery.forEach(this, in_optional_pause_ms, in_callback); // run it
};