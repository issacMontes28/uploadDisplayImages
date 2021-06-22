<template>
    <v-app>
        <div>
            <v-app-bar color="indigo lighten-1" dense dark>
                <v-toolbar-title>Image Uploader</v-toolbar-title>
                <v-spacer></v-spacer>
            </v-app-bar>
        </div>
        <v-main class="justify-center">
            <v-container grid-list-md>
                <div>
                    <div>
                        <div class="mb-2">
                            <v-progress-linear
                                max="100"
                                v-model="uploadPercentage"
                                @change="setProgress"
                                color="indigo lighten-2"
                                height="25"
                                reactive
                            >
                                <strong>{{ uploadPercentage }} %</strong>
                            </v-progress-linear>
                        </div>
                    </div>

                    <v-row no-gutters justify="center" align="center">
                        <v-col cols="10">
                            <v-file-input
                                :rules="rules"
                                accept="image/png"
                                small-chips
                                show-size
                                counter
                                multiple
                                prepend-icon="mdi-camera"
                                label="Select Images"
                                @change="selectFiles"
                                @click:clear="clearFiles"
                            ></v-file-input>
                        </v-col>

                        <v-col cols="2" class="pl-4" id="button-div">
                            <v-btn color="success" dark @click="uploadFiles">
                                Upload
                            </v-btn>
                        </v-col>
                    </v-row>

                    <div v-if="message" class="alert alert-light" role="alert">
                        <ul>
                            <li v-for="(message, index) in message.split('\n')" :key="index">
                                {{ message }}
                            </li>
                        </ul>
                    </div>
                    <br>

                    <v-data-table
                        :headers="tableHead"
                        :items="files"
                        :search="search"
                        class="elevation-1"
                        :loading="loading"
                    >
                        <template v-slot:top>
                            <v-toolbar flat>
                                <v-toolbar-title>Images</v-toolbar-title>
                                <v-divider class="mx-4" inset vertical></v-divider>
                                <v-spacer></v-spacer>

                                <v-text-field
                                    v-model="search"
                                    label="Search"
                                    single-line
                                    hide-details
                                ></v-text-field>
                                <v-divider class="mx-4" inset vertical></v-divider>
                                <v-spacer></v-spacer>

                                <v-dialog v-model="dialogDelete" max-width="520px">
                                    <v-card>
                                        <v-card-title class="headline">Are you sure you want to delete this image?</v-card-title>
                                        <v-card-actions>
                                            <v-spacer></v-spacer>
                                            <v-btn color="blue darken-1" text @click="close">Cancel</v-btn>
                                            <v-btn color="blue darken-1" text @click="deleteImageConfirmed">OK</v-btn>
                                            <v-spacer></v-spacer>
                                        </v-card-actions>
                                    </v-card>
                                </v-dialog>

                                <v-dialog v-model="dialogMessage" max-width="510px">
                                    <v-card>
                                        <v-card-title class="headline">{{ formTitle }}</v-card-title>
                                        <v-card-actions>
                                            <v-btn color="blue darken-1" text @click="close">OK</v-btn>
                                            <v-spacer></v-spacer>
                                        </v-card-actions>
                                    </v-card>
                                </v-dialog>

                            </v-toolbar>
                        </template>
                        <template slot="items" slot-scope="props">
                            <td class="text-xs-center">{{ props.item.name }}</td>
                        </template>
                        <template v-slot:item.url="{ item }">
                            <div class="text-xs-center" justify="center">
                                <v-img :src="item.url" :alt="item.name" height="80px" width="80px" justify="center"></v-img>
                            </div>
                        </template>
                        <template v-slot:item.actions="{ item }">
                            <v-icon
                                small
                                @click="deleteImage(item)"
                            >
                                mdi-delete
                            </v-icon>
                        </template>
                        <v-alert
                            class="my-4"
                            slot="no-results"
                            :value="true"
                            color="error"
                            icon="warning"
                        >
                            No results for your search
                        </v-alert>
                        <template slot="no-data">
                            <v-alert :value="!loading" color="error" icon="warning">
                                There are not registered images.
                            </v-alert>
                        </template>
                    </v-data-table>
                </div>
            </v-container>
        </v-main>

        <v-footer color="indigo lighten-1" padless>
            <v-row justify="center" no-gutters>
                <v-col class="indigo py-4 text-center white--text" cols="12">
                    {{ new Date().getFullYear() }} — <strong>Issac Montes Escamilla</strong>
                </v-col>
            </v-row>
        </v-footer>
    </v-app>
</template>

<script>
    import axios from "axios";

    export default {
        name: "ImageLoader",
        data(){
            return {
                rules: [
                    function (value) {
                        if (!value) {
                            return false;
                        }

                        let size = 0;

                        for(let image of value){
                            size += image.size;
                        }

                        return size > 2000000 ? 'Image(s) size sould be less than 2 MB' : true;
                    }
                ],
                selectedFiles: undefined,
                uploadPercentage: 0,
                message: "",
                search: "",
                formTitle: '',
                loading: false,
                files: [],
                tableHead: [
                    {
                        text: "Name",
                        align: "center",
                        sortable: true,
                        value: "name",
                    },
                    {
                        text: "Image",
                        align: "center",
                        sortable: true,
                        value: "url",
                    },
                    {
                        text: "Actions",
                        align: "center",
                        value: "actions",
                    },
                ],
                imageIndex: -1,
                dialogDelete: false,
                dialogMessage: false,
            }
        },
        mounted() {
            this.getImages();
        },
        methods: {
            close(){
                if (this.dialogDelete) {
                    this.dialogDelete = false;
                }
                if (this.dialogMessage) {
                    this.dialogMessage = false;
                }
                this.imageId = null;
                this.imageIndex = null;
                this.formTitle = '';

                return true;
            },
            selectFiles(files) {
                this.selectedFiles = files;
            },
            uploadFiles() {
                if (this.selectedFiles !== undefined) {
                    for (let i = 0; i < this.selectedFiles.length; i++) {
                        if (i === 0) {
                            this.clearFiles();
                        }
                        this.upload(i, this.selectedFiles[i]);
                    }
                } else {
                    this.formTitle = "Select one or more files first";
                    this.dialogMessage = true;
                }
            },
            clearFiles(){
                this.message = "";
                this.uploadPercentage = 0;
            },
            upload(idx, file) {
                let formData = new FormData();
                formData.append("file", file);

                return axios
                    .post(`/api/images`, formData,  {
                        headers: {
                            "Content-Type": "multipart/form-data"
                        }
                    })
                    .then((response) => {
                        this.setProgress();
                        let prevMessage = this.message ? this.message + "\n" : "";
                        this.message = prevMessage + file.name + ': ' + response.data.response.message;
                        this.files.unshift({id:response.data.id ,url: response.data.response.url, name:file.name});
                    })
                    .catch((error) => {
                        let warn = ',' +error.response.data.message.slice(0, -1) + ': ' + error.response.data.errors.file[0];
                        this.message = "Could not upload the file:" + file.name + ' ' + warn;
                    });
            },
            setProgress(){
                let val = this.selectedFiles === undefined ?  0 : Math.round(this.uploadPercentage + ( 100 / this.selectedFiles.length));
                this.uploadPercentage = val === 99 && idx === (this.selectedFiles.length - 1) ? 100 : val;
            },
            getImages() {
                return axios
                    .get(`/api/images`)
                    .then( res => {
                        this.files = res.data;
                    })
                    .catch(err => console.log(err));
            },
            deleteImage(image){
                this.imageIndex = this.files.indexOf(image);
                this.imageId = image.id;
                this.dialogDelete = true;
            },
            deleteImageConfirmed(){
                fetch(`api/images/${this.imageId}`, {
                    method: 'delete'
                })
                    .then(res => res.json())
                    .then(data => {
                        this.files.splice(this.imageIndex, 1);
                        this.dialogDelete = false;
                        this.formTitle = data.response;
                        this.dialogMessage = true;
                    })
                    .catch(err => console.log(err));
            },
        }
    }
</script>
