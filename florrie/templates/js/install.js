
document.addEventListener("DOMContentLoaded", function() {

	var comicUrl = document.getElementById("comic-url");

	if(comicUrl && comicUrl.value.length > 0) {

		return;
	}

	baseUrl = /http:\/\/[\w-.]+\//;
	comicUrl.value = baseUrl.exec(window.location);
});
