function TrackSmart(container)
{
    this.container = container;
    this.user = null;

    this.state = false;
}

function TrackSmart(container, user)
{
    this.container = container;
    this.user = user;

    this.state = false;
}

TrackSmart.prototype.build = function()
{
    if (this.container === null)
    {
        throw 'Container can not be null!';
    }

    if (this.state)
    {
        throw 'Module is enabled';
    }

    (function (w, d, s, l, i, u)
    {
        w[l] = w[l] || [];

        if (u !== null)
        {
            w[l].push({
                'gtm.start': new Date().getTime(), event: 'gtm.js', user_id: u
            });
        }
        else
        {
            w[l].push({
                'gtm.start': new Date().getTime(), event: 'gtm.js'
            });
        }

        let f = d.getElementsByTagName(s)[0],
            j = d.createElement(s),
            dl = l != "dataLayer" ? "&l=" + l : "";
        j.async = true;
        j.src = "https://www.googletagmanager.com/gtm.js?id=" + i + dl;
        f.parentNode.insertBefore(j, f);
    })(window, document, "script", "dataLayer", this.container, this.user ?? null);

    this.state = true;
}

TrackSmart.prototype.process = function(event, ecommerce = {})
{
    if (!event || !ecommerce)
    {
        throw 'Params can not be null';
    }

    if (!this.state || typeof dataLayer === 'undefined')
    {
        throw 'DataLayer can not be null';
    }

    let body = {
        'event': event,
        'ecommerce': ecommerce
    }

    try
    {
        dataLayer.push(body);
    }
    catch (ex)
    {
        throw ex;
    }
}

TrackSmart.prototype.destroy = function()
{
    if (!this.state)
    {
        throw 'Module is disabled'
    }

    if (typeof dataLayer !== 'undefined')
    {
        delete dataLayer;
    }

    this.state = false;
}
