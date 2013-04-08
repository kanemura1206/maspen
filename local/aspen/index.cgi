#!/usr/local/bin/konoha

import("posix.process");

load("./k/template.k");
load("./k/CGI.k");

void main() {
	Template t = Template.getTemplate("index.html");
	CGI cgi = new CGI(System.getenv("QUERY_STRING"));
	t.set("CMID", cgi.getParam("cmid"));
	t.set("USERID", cgi.getParam("userid"));
	t.set("PATH", "http://ec2-54-244-187-206.us-west-2.compute.amazonaws.com/aspen2/local/aspen/");
	t.set("ROOTURL", "http://ec2-54-244-187-206.us-west-2.compute.amazonaws.com/aspen2/");
	t.set("TITLE", "Konoha");
	t.set("COPY", "Konoha Project");
	t.set("URL", System.getenv("QUERY_STRING"));
	stdout.println("Content-Type: text/html; charset=utf-8\n");
	stdout.println(t.render());
}

main();
