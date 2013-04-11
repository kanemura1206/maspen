#!/usr/local/bin/konoha

import("posix.process");

load("./k/config.k");
load("./k/template.k");
load("./k/CGI.k");

void main() {
	Template t = Template.getTemplate("index.html");
	CGI cgi = new CGI(System.getenv("QUERY_STRING"));
	t.set("CMID", cgi.getParam("cmid"));
	t.set("USERID", cgi.getParam("userid"));
	t.set("PATH", global.CONFIG_ASPEN_ROOT_URL);
	t.set("ROOTURL", global.CONFIG_MOODLE_ROOT_URL);
	t.set("TITLE", "Konoha");
	t.set("COPY", "Konoha Project");
	t.set("URL", System.getenv("QUERY_STRING"));
	stdout.println("Content-Type: text/html; charset=utf-8\n");
	stdout.println(t.render());
}

main();
