-- Set the response content type back to HTML
ngx.header.content_type = 'text/html';

ngx.req.read_body()

local page_html = ngx.location.capture("/index.htm").body

local before_content, placeholder, after_content
before_content, placeholder, after_content = page_html:match(
  string.format("^(.*)(%s)(.*)$", html_head_tag_replacement_str)
)

-- check for placeholder to replace
if not placeholder then
  ngx.log(ngx.STDERR, "WARNING: Meta tag placeholder was not found in page html.")
  ngx.say(page_html)
  return
else
  -- start the response
  ngx.print(before_content)
end

ngx.req.set_header("Accept", "application/json")
local server_info_resp = ngx.location.capture("/_internal_api/info")
if server_info_resp.status ~= 200 then
  ngx.log(ngx.STDERR, "Failed to acquire server info from szurubooru API, unable to generate meta tags: HTTP status "..server_info_resp.status)
  ngx.print(after_content)
  return
end
local server_info = cjson.decode(server_info_resp.body)

local additional_tags = ""
local function add_meta_tag (property, content)
  additional_tags = additional_tags .. "<meta property=\"" .. property .. "\" content=\"" .. content:gsub('"', '\\"') .. "\"/>"
end

-- Add the site name tag
add_meta_tag("og:site_name", server_info.config.name)
add_meta_tag("og:url", ngx.var.external_host_url .. ngx.var.request_uri)

if ngx.var.request_uri_path:match('^/post') then
  local post_info = cjson.decode((ngx.location.capture("/_internal_api"..ngx.var.request_uri_path)).body)
  -- If no permission to access, fields will be nil, thus cannot be concat'd
  if post_info.contentUrl then
    add_meta_tag("og:image", ngx.var.external_host_url .. '/' .. post_info.contentUrl)
  end
  if post_info.id then
    add_meta_tag("og:title", server_info.config.name .. " - Post " .. post_info.id)
  end
end

-- Once tags have been generated, write them and then finish the response
ngx.print(additional_tags)
ngx.print(after_content)
