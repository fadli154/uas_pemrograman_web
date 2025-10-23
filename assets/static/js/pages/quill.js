var snow = new Quill("#snow", {
  theme: "snow",
  placeholder: "Tulis deskripsi publikasi di sini...",
  modules: {
    toolbar: [
      [{ header: [1, 2, false] }],
      ["bold", "italic", "underline"],
      [{ list: "ordered" }, { list: "bullet" }],
    ],
  },
});
var bubble = new Quill("#bubble", {
  theme: "bubble",
});
new Quill("#full", {
  bounds: "#full-container .editor",
  placeholder: "Tulis deskripsi publikasi di sini...",
  theme: "snow",
});
