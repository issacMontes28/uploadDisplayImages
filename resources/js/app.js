require('./bootstrap');

import Vue from 'vue';
import Vuetify from 'vuetify';
import 'vuetify/dist/vuetify.min.css';

Vue.use(Vuetify);

Vue.component(
    'image-loader',
    require('./components/ImageLoader.vue').default
);

const app = new Vue({
    el: '#app',
    vuetify: new Vuetify(),
});
