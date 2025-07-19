// src/config.js
const api_url = import.meta.env.VITE_API_URL;

export default {
    debug: true,

    apiBaseUrl: api_url,

    /* Color theme */
    primary_color: 'blue',

    /* toast setting */
    toast_lifetime: 5000, // in ms

    /* Api endpoins */
    not_found_page: '/not_found',
    /* Api endpoins */
    endpoint_login: 'controlers/login_controler.php',
    endpoint_admin: 'controlers/admin_controler.php',
    endpoint_ticket: 'controlers/ticket_controler.php',
    endpoint_volunteer: 'controlers/volunteer_controler.php'
};
