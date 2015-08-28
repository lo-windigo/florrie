
document.addEventListener("DOMContentLoaded", function() {

	var comicUrl = document.getElementById("florrie-url");

	if(comicUrl && comicUrl.value.length > 0) {

		return;
	}

	baseUrl = /http:\/\/[\w-.]+\//;
	comicUrl.value = baseUrl.exec(window.location);
});
