import axios from "axios";

export default axios.create({
    baseURL: "http://127.0.0.0:8000",
    headers: {
        "Content-Type": "multipart/form-data"
    }
});
