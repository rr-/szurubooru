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
  -- send partial content to the client to allow preloading the app
  ngx.flush()
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
  additional_tags = additional_tags .. "<meta property=\"" .. property .. "\" content=\"" .. tostring(content):gsub('"', '\\"') .. "\"/>"
end

-- Add the site name tag
add_meta_tag("og:site_name", server_info.config.name)
add_meta_tag("og:url", ngx.var.external_host_url .. ngx.var.request_uri)

if ngx.var.request_uri_path:match('^/post') then -- Post metadata
  -- check if posts are accessible to anonymous users:
  if server_info.config.privileges["posts:view"] == "anonymous" then
    local post_info = cjson.decode((ngx.location.capture("/_internal_api"..ngx.var.request_uri_path)).body)
    add_meta_tag("og:title", server_info.config.name .. " - Post " .. post_info.id)
    local og_media_prefix
    if post_info.type == "image" then
      og_media_prefix = "og:image"
    elseif post_info.type == "video" then
      og_media_prefix = "og:video"
    end
    add_meta_tag(og_media_prefix..":url", ngx.var.external_host_url .. '/' .. post_info.contentUrl)
    add_meta_tag(og_media_prefix..":width", post_info.canvasWidth)
    add_meta_tag(og_media_prefix..":height", post_info.canvasHeight)
  else
    -- no permission to retrieve post data
    add_meta_tag("og:title", server_info.config.name .. " - Login required")
  end
elseif ngx.var.request_uri_path:match('^/user/(.*)$') then -- User metadata
  local username = ngx.var.request_uri_path:match('^/user/(.*)/?$')
  add_meta_tag("og:title", server_info.config.name .. " - User " .. username)
  -- check for permission to access user profiles
  if server_info.config.privileges["users:view"] == "anonymous" then
    local user_info = cjson.decode((ngx.location.capture("/_internal_api/"..username)).body)
    -- TODO
  else
    -- TODO
  end
end

-- Once tags have been generated, write them and then finish the response
ngx.print(additional_tags)
ngx.print(after_content)
