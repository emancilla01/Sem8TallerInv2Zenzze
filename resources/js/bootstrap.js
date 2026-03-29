import 'bootstrap';
// import '../sass/app.scss';
// import 'bootstrap/dist/css/bootstrap.min.css';
import '../css/app.css';
import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
