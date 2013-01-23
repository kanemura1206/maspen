#!/usr/local/bin/minikonoha

import("posix.process");

load("./k/template.k");
load("./k/CGI.k");

void main() {
	Template t = Template.getTemplate("index.html");
	CGI cgi = new CGI(System.getenv("QUERY_STRING"));
	t.set("ID", cgi.getParam("id"));
	t.set("USERID", cgi.getParam("userid"));
	t.set("PATH", "http://konoha.ubicg.ynu.ac.jp/maspen/local/aspen/");
	t.set("ROOTURL", "http://konoha.ubicg.ynu.ac.jp/maspen/");
	t.set("TITLE", "Konoha");
	t.set("COPY", "Konoha Project");
	t.set("URL", System.getenv("QUERY_STRING"));
	stdout.println("Content-Type: text/html; charset=utf-8\n");
	stdout.println(t.render());
}

main();
